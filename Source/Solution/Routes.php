<?php

namespace PhpRepos\WebRouter\Solution\Routes;

use PhpRepos\WebRouter\Business\Attributes\Method;
use PhpRepos\WebRouter\Solution\Exceptions\MethodNotAllowedException;
use PhpRepos\WebRouter\Solution\Exceptions\ParameterValidationException;
use PhpRepos\WebRouter\Infra\Arrays;
use PhpRepos\WebRouter\Infra\Reflections;
use PhpRepos\WebRouter\Infra\Strings;
use ReflectionException;
use ReflectionNamedType;
use function PhpRepos\Logger\API\Logs\debug;

function url_pattern(string $root, string $route, string $suffix): string
{
    $relative = Strings\replace_first_occurrence($route, $root, '');

    if (Strings\ends_with($relative, 'index' . $suffix)) {
        $relative = Strings\before_last_occurrence($relative, 'index' . $suffix);
    } else {
        $relative = Strings\before_last_occurrence($relative, $suffix);
    }

    return $relative === '' ? '/' : $relative;
}

/**
 * Sort routes by specificity (fewer parameters first).
 *
 * Routes with fewer dynamic parameters are prioritized to ensure
 * more specific routes are matched before generic ones.
 *
 * @param array $routes Array of routes to sort
 * @return array Sorted routes array
 */
function sort(array $routes): array
{
    debug('Sorting routes by specificity', ['route_count' => count($routes)]);

    return Arrays\sort($routes, function ($a, $b) {
        return substr_count($a['pattern'], '{') <=> substr_count($b['pattern'], '{');
    });
}

/**
 * Match a URL path against a route pattern.
 *
 * @param string $pattern The route pattern
 * @param string $url_path The URL path to match
 * @return array|null Parameters if matched, null if no match
 */
function match_pattern(string $pattern, string $url_path): ?array
{
    debug('Matching URL path against route pattern', ['pattern' => $pattern, 'url_path' => $url_path]);

    $pattern_parts = explode('/', trim($pattern, '/'));
    $url_parts = explode('/', trim($url_path, '/'));

    $optional_count = Arrays\reduce($pattern_parts, fn ($c, $p) => str_starts_with($p, '{?') ? ++$c : $c, 0);

    if (count($url_parts) !== count($pattern_parts) && count($url_parts) !== count($pattern_parts) - $optional_count) {
        return null;
    }

    $parameters = [];
    foreach ($pattern_parts as $position => $part) {
        if (Strings\starts_with($part, '{')) {
            $param_name = Strings\starts_with($part, '{?') ? substr($part, 2, -1) : substr($part, 1, -1);
            $parameters[$param_name] = isset($url_parts[$position]) ? urldecode($url_parts[$position]) : null;
        } elseif (!isset($url_parts[$position]) || Strings\to_lower_case($part) !== Strings\to_lower_case($url_parts[$position])) {
            return null;
        }
    }

    return Arrays\filter($parameters, fn ($v) => $v !== null);
}

/**
 * Validate that an HTTP method is allowed for a handler.
 *
 * @param callable $handler The route handler
 * @param string $method The HTTP method
 * @param string $url The URL/pattern for error reporting
 * @return void
 * @throws MethodNotAllowedException If method is not allowed
 * @throws ReflectionException
 */
function validate_method(callable $handler, string $method, string $url): void
{
    debug('Validating HTTP method for handler', ['method' => $method, 'url' => $url]);

    $attributes = Reflections\get_attributes($handler, Method::class);
    if (empty($attributes)) return;

    $allowed_methods = [];
    foreach ($attributes as $attr) {
        $allowed_methods[] = Strings\to_upper_case($attr->newInstance()->method);
    }

    debug('Method validation result', ['requested_method' => $method, 'allowed_methods' => $allowed_methods]);

    if (!in_array(Strings\to_upper_case($method), $allowed_methods)) {
        throw new MethodNotAllowedException($url, $allowed_methods);
    }
}

/**
 * Validate and prepare parameters for a handler.
 *
 * @param callable $handler The route handler
 * @param array $url_parameters Parameters extracted from URL
 * @param array $variables Additional variables
 * @return array Prepared parameters
 * @throws ParameterValidationException If parameter validation fails
 * @throws ReflectionException
 */
function validate_parameters(callable $handler, array $url_parameters, array $variables): array
{
    debug('Validating parameters for handler', ['url_parameters' => $url_parameters, 'variables' => $variables]);

    $parameters = Reflections\get_parameters($handler);
    $prepared = [];

    foreach ($parameters as $param) {
        $param_name = $param->getName();
        $value = $url_parameters[$param_name] ?? $variables[$param_name] ?? null;

        if ($value !== null) {
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && !$type->allowsNull()) {
                $value = cast_to_type($value, $type->getName(), $param_name);
            }
            $prepared[$param_name] = $value;
        } elseif ($param->isDefaultValueAvailable()) {
            $prepared[$param_name] = $param->getDefaultValue();
        } else {
            $prepared[$param_name] = null;
        }
    }

    debug('Parameters validated and prepared', ['prepared_parameters' => $prepared]);

    return $prepared;
}

/**
 * Cast a value to the expected type.
 *
 * @param mixed $value The value to cast
 * @param string $expected_type The expected type
 * @param string $param_name The parameter name
 * @return mixed The casted value
 * @throws ParameterValidationException If casting fails
 */
function cast_to_type(mixed $value, string $expected_type, string $param_name): mixed
{
    if ($expected_type === 'int') {
        if ($value == (int) $value) return (int) $value;
        throw new ParameterValidationException($param_name, 'int', gettype($value));
    }

    if ($expected_type === 'bool') {
        if ($value === 'true' || $value === '1' || $value === true) return true;
        if ($value === 'false' || $value === '0' || $value === false) return false;
        throw new ParameterValidationException($param_name, 'bool', gettype($value));
    }

    return $value;
}
