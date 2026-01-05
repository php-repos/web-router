<?php

namespace PhpRepos\WebRouter\Business\Router;

use PhpRepos\WebRouter\Business\Outcome;
use PhpRepos\WebRouter\Business\Signals\RequestReceived;
use PhpRepos\WebRouter\Business\Signals\RouteDetected;
use PhpRepos\WebRouter\Business\Signals\RouteNotFoundForUrl;
use PhpRepos\WebRouter\Business\Signals\DisallowedMethodDetected;
use PhpRepos\WebRouter\Business\Signals\ExecutingHandler;
use PhpRepos\WebRouter\Business\Signals\HandlerExecuted;
use PhpRepos\WebRouter\Business\Signals\FindingRoute;
use PhpRepos\WebRouter\Business\Signals\RouteFindingFailed;
use PhpRepos\WebRouter\Solution\Routes;
use PhpRepos\WebRouter\Solution\URLs;
use PhpRepos\WebRouter\Solution\Exceptions\MethodNotAllowedException;
use PhpRepos\WebRouter\Solution\Exceptions\ParameterValidationException;
use Throwable;
use function PhpRepos\Observer\API\Bus\send;

/**
 * Find a matching route for the given URL.
 *
 * Searches through routes to find one that matches the URL pattern.
 * Emits RouteDetected signal when a match is found.
 *
 * @param array $routes Array of routes to search
 * @param string $url The URL to match
 * @return Outcome Success with matched route and parameters, or failure if no match
 */
function find(array $routes, string $url): Outcome
{
    $sorted_routes = Routes\sort($routes);
    $url_path = URLs\path($url);

    send(FindingRoute::for_url($url, count($sorted_routes)));

    foreach ($sorted_routes as $route) {
        $parameters = Routes\match_pattern($route['pattern'], $url_path);
        if ($parameters !== null) {
            send(RouteDetected::route($route['pattern']));
            return new Outcome(true, 'Route found', [
                'route' => $route,
                'parameters' => $parameters,
            ]);
        }
    }

    send(RouteFindingFailed::for_url($url, count($sorted_routes)));

    return new Outcome(false, "Route not found for URL: {$url_path}", ['url' => $url_path]);
}

/**
 * Respond to an HTTP request by routing it to the appropriate handler.
 *
 * Specification:
 * 1. Find a route from routes by given URL
 * 2. Validate the HTTP method is allowed for the route
 * 3. Validate and prepare parameters
 * 4. Execute the handler with parameters
 * 5. Return the response
 *
 * @param array $routes Array of routes [['pattern' => string, 'handler' => callable], ...]
 * @param string $url The request URL
 * @param string $method The HTTP method (GET, POST, etc.)
 * @param array $variables Additional variables to pass to handler (e.g., $_GET, $_POST)
 * @return Outcome The result containing the response or error
 */
function respond(array $routes, string $url, string $method, array $variables = []): Outcome
{
    send(RequestReceived::to($url, $method, $variables));

    $outcome = find($routes, $url);
    if (!$outcome->success) {
        send(RouteNotFoundForUrl::to($url, $method));
        return new Outcome(false, $outcome->message, ['url' => $url, 'method' => $method]);
    }

    $route = $outcome->data['route'];
    $parameters = $outcome->data['parameters'];

    try {
        $url_path = URLs\path($url);
        Routes\validate_method($route['handler'], $method, $url_path);

        $prepared_params = Routes\validate_parameters($route['handler'], $parameters, $variables);

        send(ExecutingHandler::using($route['pattern'], $prepared_params));
        $response = ($route['handler'])(...array_values($prepared_params));
        send(HandlerExecuted::with_response($response, $route['pattern']));

        return new Outcome(true, 'Request handled successfully', ['response' => $response]);
    } catch (MethodNotAllowedException $e) {
        send(DisallowedMethodDetected::for($url_path, $method, $e->allowed_methods));
        return new Outcome(false, $e->getMessage(), ['url' => $url, 'method' => $method]);
    } catch (ParameterValidationException $e) {
        return new Outcome(false, $e->getMessage(), ['url' => $url]);
    } catch (Throwable $e) {
        return new Outcome(false, "Internal error: {$e->getMessage()}", ['url' => $url]);
    }
}
