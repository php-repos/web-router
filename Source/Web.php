<?php

namespace PhpRepos\Web\Web;

use PhpRepos\Web\Attributes\Method;
use PhpRepos\Web\Exceptions\MethodNotAllowedException;
use PhpRepos\Web\Exceptions\RouteNotFoundException;
use PhpRepos\Web\Exceptions\ParameterValidationException;
use PhpRepos\Web\Router;
use PhpRepos\Web\Signals\DisallowedMethodDetected;
use PhpRepos\Web\Signals\HandlerExecuted;
use PhpRepos\Web\Signals\RouteDetected;
use PhpRepos\Web\Signals\RouteNotFoundForUrl;
use PhpRepos\Web\Signals\ExecutingHandler;
use PhpRepos\Web\Signals\RequestReceived;
use ReflectionAttribute;
use ReflectionFunction;
use ReflectionNamedType;
use function PhpRepos\Datatype\Arr\reduce;
use function PhpRepos\Datatype\Str\between;
use function PhpRepos\Observer\Observer\send;

function respond(Router $routes, string $url, string $method, ?array $variables = []): mixed
{
    send(RequestReceived::to($url, $method, $variables));

    $routes->sort_key(function ($a, $b) {
        $count_a = substr_count($a, '{');
        $count_b = substr_count($b, '{');

        return $count_a <=> $count_b;
    });

    $parsed_url = parse_url($url);
    $url_path = $parsed_url['path'] ?? '/';

    $url_path = preg_replace_callback('/%(?![2][Ff])[^%]+/', function ($match) {
        return urldecode($match[0]);
    }, $url_path);

    $route_params = function (string $url_pattern) use ($url_path) {
        $params = [];
        $possible_route_parts = explode('/', trim($url_path, '/'));
        $route_parts = explode('/', trim($url_pattern, '/'));

        $count_optionals = reduce($route_parts, function (int $counter, string $part) {
            return str_starts_with($part, '{?') ? ++$counter : $counter;
        }, 0);

        if (count($possible_route_parts) !== count($route_parts) && count($possible_route_parts) !== count($route_parts) - $count_optionals) {
            return null;
        }

        foreach ($route_parts as $position => $part) {
            if (str_starts_with($part, '{')) {
                if (str_starts_with($part, '{?')) {
                    $param_name = between($part, '{?', '}');
                    $params[$param_name] = isset($possible_route_parts[$position]) ? urldecode($possible_route_parts[$position]) : null;
                } else {
                    $param_name = between($part, '{', '}');
                    $params[$param_name] = urldecode($possible_route_parts[$position]);
                }
            } elseif (strtolower($part) !== strtolower($possible_route_parts[$position])) {
                return null;
            }
        }

        return $params;
    };

    $detected_route = null;
    $params = [];

    foreach ($routes as $route) {
        $test_params = $route_params($route['key']);

        if ($test_params !== null) {
            $detected_route = $route;
            $params = array_filter($test_params, fn ($value) => ! is_null($value));
            break;
        }
    }

    if (!$detected_route) {
        send(RouteNotFoundForUrl::to($url_path, $method));
        throw new RouteNotFoundException($url_path);
    }

    send(RouteDetected::route($detected_route['key']));

    $handler = $detected_route['value'];

    $reflection = new ReflectionFunction($handler);

    $parameters = $reflection->getParameters();
    foreach ($parameters as $param) {
        $param_name = $param->getName();

        if (! isset($params[$param_name]) && isset($variables[$param_name])) {
            $params[$param_name] = $variables[$param_name];
        }

        if (isset($params[$param_name])) {
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && !$type->allowsNull()) {
                $value = $params[$param_name];
                $expected_type = $type->getName();

                if ($expected_type === 'int') {
                    $params[$param_name] = $value == (int) $value ? (int) $value : throw new ParameterValidationException($param_name, 'int', 'string');
                } else if ($expected_type === 'bool') {
                    if ($value === 'true' || $value === '1') {
                        $params[$param_name] = true;
                    } else if ($value === 'false' || $value === '0') {
                        $params[$param_name] = false;
                    } else {
                        throw new ParameterValidationException($param_name, 'bool', 'string');
                    }
                }
            }
        }

        if (!isset($params[$param_name])) {
            $params[$param_name] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
        }
    }

    $attributes = $reflection->getAttributes(Method::class, ReflectionAttribute::IS_INSTANCEOF);

    if (! empty($attributes)) {
        $allowed_methods = [];
        foreach ($attributes as $attribute) {
            $method_instance = $attribute->newInstance();
            $allowed_methods[] = strtoupper($method_instance->method);
        }

        if (!in_array(strtoupper($method), $allowed_methods)) {
            send(DisallowedMethodDetected::for($detected_route['key'], $method, $allowed_methods));
            throw new MethodNotAllowedException($url_path);
        }
    }
    send(ExecutingHandler::using($detected_route['key'], $params));
    $response = $handler(...$params);
    send(HandlerExecuted::with_response($response, $detected_route['key']));

    return $response;
}
