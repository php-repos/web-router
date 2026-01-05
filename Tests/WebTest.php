<?php

use PhpRepos\Observer\API\Event;
use PhpRepos\Observer\API\Signal;
use PhpRepos\WebRouter\Business\Attributes\Method;
use PhpRepos\WebRouter\Solution\Exceptions\MethodNotAllowedException;
use PhpRepos\WebRouter\Solution\Exceptions\ParameterValidationException;
use PhpRepos\WebRouter\Business\Signals\HandlerExecuted;
use PhpRepos\WebRouter\Business\Signals\DisallowedMethodDetected;
use PhpRepos\WebRouter\Business\Signals\RouteDetected;
use PhpRepos\WebRouter\Business\Signals\RouteNotFoundForUrl;
use PhpRepos\WebRouter\Business\Signals\ExecutingHandler;
use PhpRepos\WebRouter\Business\Signals\RequestReceived;
use PhpRepos\WebRouter\Business\Signals\FindingRoute;
use PhpRepos\WebRouter\Business\Signals\RouteFindingFailed;
use PhpRepos\WebRouter\Business\Finder;
use PhpRepos\WebRouter\Business\Router;
use function PhpRepos\Observer\API\Bus\subscribe;
use function PhpRepos\TestRunner\Assertions\assert_true;
use function PhpRepos\TestRunner\Assertions\assert_false;
use function PhpRepos\TestRunner\Runner\test;

$outcome = Finder\path('Tests/Routes');
if (!$outcome->success) {
    throw new Exception("Failed to load test routes: {$outcome->message}");
}
$test_routes = $outcome->data['routes'];

test(
    title: 'it should use index.php when there is no other file to handle GET request for home',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/', 'GET');

        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('Hello World!' === $outcome->data['response'], 'Did not return expected response: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should return failure when there is no handler for the given URL',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/not-found', 'GET');

        assert_false($outcome->success, 'Expected failure outcome for non-existent route');
        assert_true('Route not found for URL: /not-found' === $outcome->message, 'The outcome message is not what we expect: ' . $outcome->message);
    }
);

test(
    title: 'it should use index.php for any method when not specified',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/', 'POST');

        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('Hello World!' === $outcome->data['response'], 'Did not return expected response: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should use index.php when there is sub urls',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/users', 'GET');

        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('List of users' === $outcome->data['response'], 'Did not return expected response: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should use index.php when there is sub urls having trailing /',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/users/', 'GET');

        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('List of users' === $outcome->data['response'], 'Did not return expected response: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should return failure when the index does not support the method',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/users', 'POST');

        assert_false($outcome->success, 'Expected failure outcome for disallowed method');
        assert_true('Method is not allowed for URL: /users' === $outcome->message, 'The outcome message is not what we expect: ' . $outcome->message);
    }
);

test(
    title: 'it should use method file when there is a file for method',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/v1/posts/get', 'GET');

        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('List of posts' === $outcome->data['response'], 'Did not return expected response: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should use method file when there is a file for method and the URL has a trailing /',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/v1/posts/get/', 'GET');

        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('List of posts' === $outcome->data['response'], 'Did not return expected response: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should find the router when there is encoded space',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/v1/posts/search%20by', 'GET');

        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('Search on posts' === $outcome->data['response'], 'Did not return expected response: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should pass route parameters to the handler',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/users/123/show', 'GET');

        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('Showing user 123' === $outcome->data['response'], 'Did not return expected response: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should pass route parameters to the handler when it is defined on the handler filename',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/v1/posts/123', 'GET');

        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('Showing post 123' === $outcome->data['response'], 'Did not return expected response: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should find the handler with different cases',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/USERS/123/posts', 'GET');

        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('Showing posts belong to user 123' === $outcome->data['response'], 'Did not return expected response: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should pass multiple route parameters',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/users/123/posts/456', 'GET');

        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('Showing post 456 that belongs to 123' === $outcome->data['response'], 'Did not return expected response: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should use method file when there is a file for any number of methods',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/users/123/edit', 'PATCH');

        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('Edit user 123' === $outcome->data['response'], 'Did not return expected response: ' . $outcome->data['response']);

        $outcome = Router\respond($test_routes, '/users/123/edit', 'PUT');

        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('Edit user 123' === $outcome->data['response'], 'Did not return expected response: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should handle encoded / as parameters',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/users/123%2F456/posts/456', 'GET');
        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('Showing post 456 that belongs to 123/456' === $outcome->data['response'], 'Did not return expected response: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should not use .. for path change',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/users/../posts/456', 'GET');
        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('Showing post 456 that belongs to ..' === $outcome->data['response'], 'Did not return expected response: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should return whatever the handler file returns',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/specific', 'GET');
        assert_true(['Hello' => 'World'] === $outcome->data['response'], 'Did not return expected response');
    }
);

test(
    title: 'it should handle optional parameters',
    case: function () use ($test_routes) {
         $outcome = Router\respond($test_routes, '/products/cars', 'GET');
         assert_true($outcome->success, 'Expected successful outcome');
        assert_true('Showing products in category cars' === $outcome->data['response'], 'Did not return expected response when optional param passed: ' . $outcome->data['response']);

        $outcome = Router\respond($test_routes, '/products', 'GET');
        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('Showing products in category All' === $outcome->data['response'], 'Did not return expected response when optional parameter is absent: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should find proper match when there are similar optional routes',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/products/type', 'GET');
        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('Get products group by types' === $outcome->data['response'], 'Did not return expected responses: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should handle empty URL by defaulting to root handler',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '', 'GET');
        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('Hello World!' === $outcome->data['response'], 'Did not return expected response for empty URL: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should normalize multiple leading slashes to root',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '////', 'GET');
        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('Hello World!' === $outcome->data['response'], 'Did not normalize multiple slashes: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should ignore query strings and route correctly',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/users/123/show?format=json', 'GET');
        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('Showing user 123' === $outcome->data['response'], 'Did not ignore query string: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should return failure when the type is not what we want',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/users/not_valid/delete', 'DELETE');

        assert_false($outcome->success, 'Expected failure outcome for type validation error');
        assert_true('Expected type int for user_id but string provided.' === $outcome->message, 'Message is not what we expected: ' . $outcome->message);
    }
);

test(
    title: 'it should pass null for optional params when there is no value, and use optional value when it is defined',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/posts/123', 'GET');

        assert_true("Optional post 123 with title null" === $outcome->data['response'], 'Response is not expected: ' . $outcome->data['response']);
    },
);

test(
    title: 'it should pass handle bool for params',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/posts/123/true', 'DELETE');
        assert_true("Delete post ID 123 with force as true" === $outcome->data['response'], 'Response is not expected for true: ' . $outcome->data['response']);

        $outcome = Router\respond($test_routes, '/posts/123/false', 'DELETE');
        assert_true("Delete post ID 123 with force as false" === $outcome->data['response'], 'Response is not expected for false: ' . $outcome->data['response']);

        $outcome = Router\respond($test_routes, '/posts/123/1', 'DELETE');
        assert_true("Delete post ID 123 with force as true" === $outcome->data['response'], 'Response is not expected for 1: ' . $outcome->data['response']);

        $outcome = Router\respond($test_routes, '/posts/123/0', 'DELETE');
        assert_true("Delete post ID 123 with force as false" === $outcome->data['response'], 'Response is not expected for 0: ' . $outcome->data['response']);

        $outcome = Router\respond($test_routes, '/posts/123/not_true', 'DELETE');
        assert_false($outcome->success, 'Expected failure outcome for bool validation error');
        assert_true('Expected type bool for force but string provided.' === $outcome->message, 'Message is not what we expected: ' . $outcome->message);
    },
);

test(
    title: 'it should pass variables from the given variables',
    case: function () use ($test_routes) {
        $outcome = Router\respond($test_routes, '/posts/create', 'POST', ['title' => 'Post title', 'image' => ['filename' => 'filename.png', 'extension' => 'jpg']]);

        assert_true($outcome->success, 'Expected successful outcome');
        assert_true('Post with title Post title and description null and image filename.png-jpg has been created.' === $outcome->data['response'], 'Respond does not match: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should send specific signals when there is a match and response',
    case: function () {
        $signals = [];

        subscribe(function (Signal $signal) use (&$signals) {
            $signals[] = $signal;
        });

        $routes = [
            ['pattern' => '/', 'handler' => fn () => 'hello world']
        ];

        $outcome = Router\respond($routes, '/', 'GET');

        assert_true($outcome->success && $outcome->data['response'] === 'hello world');

        assert_true($signals[0] instanceof RequestReceived);
        assert_true($signals[0]->title === 'A web request received', "Expected title 'A web request received', got: {$signals[0]->title}");
        assert_true($signals[0]->details == ['url' => '/', 'method' => 'GET', 'variables' => []], "Unexpected details for RequestReceived");

        assert_true($signals[1] instanceof FindingRoute);
        assert_true($signals[1]->title === 'Finding route for URL.', "Expected title 'Finding route for URL.', got: {$signals[1]->title}");
        assert_true($signals[1]->details == ['url' => '/', 'route_count' => 1], "Unexpected details for FindingRoute");

        assert_true($signals[2] instanceof RouteDetected);
        assert_true($signals[2]->title === 'Web route detected', "Expected title 'Web route detected', got: {$signals[2]->title}");
        assert_true($signals[2]->details == ['route' => '/'], "Unexpected details for RouteDetected");

        assert_true($signals[3] instanceof ExecutingHandler);
        assert_true($signals[3]->title === 'Executing the route handler', "Expected title 'Executing the route handler', got: {$signals[3]->title}");
        assert_true($signals[3]->details == ['route' => '/', 'params' => []], "Unexpected details for ExecutingHandler");

        assert_true($signals[4] instanceof HandlerExecuted);
        assert_true($signals[4]->title === 'Route handler finished execution', "Expected title 'Route handler finished execution', got: {$signals[4]->title}");
        assert_true($signals[4]->details == ['response' => 'hello world', 'route' => '/'], "Unexpected details for HandlerExecuted");
    }
);

test(
    title: 'it should send specific signals when route not detected',
    case: function () {
        $signals = [];

        subscribe(function (Event $signal) use (&$signals) {
            $signals[] = $signal;
        });

        $routes = [];

        $outcome = Router\respond($routes, '/', 'GET');
        assert_false($outcome->success, 'Expected failure outcome for route not found');

        assert_true($signals[0] instanceof RequestReceived);
        assert_true($signals[0]->title === 'A web request received', "Expected title 'A web request received', got: {$signals[0]->title}");
        assert_true($signals[0]->details == ['url' => '/', 'method' => 'GET', 'variables' => []], "Unexpected details for RequestReceived");

        assert_true($signals[1] instanceof RouteFindingFailed);
        assert_true($signals[1]->title === 'Route finding failed for URL.', "Expected title 'Route finding failed for URL.', got: {$signals[1]->title}");
        assert_true($signals[1]->details == ['url' => '/', 'route_count' => 0], "Unexpected details for RouteFindingFailed");

        assert_true($signals[2] instanceof RouteNotFoundForUrl);
        assert_true($signals[2]->title === 'Route not found for the given URL', "Expected title 'Route not found for the given URL', got: {$signals[2]->title}");
        assert_true($signals[2]->details == ['url' => '/', 'method' => 'GET'], "Unexpected details for RouteNotFoundForUrl");
    }
);

test(
    title: 'it should send specific signals when route detects but method is not allowed',
    case: function () {
        $signals = [];

        subscribe(function (Event $signal) use (&$signals) {
            $signals[] = $signal;
        });

        $routes = [
            ['pattern' => '/', 'handler' => #[Method('GET'), Method('DELETE')]fn () => 'hello world']
        ];

        $outcome = Router\respond($routes, '/', 'POST');
        assert_false($outcome->success, 'Expected failure outcome for method not allowed');

        assert_true($signals[0] instanceof RequestReceived);
        assert_true($signals[0]->title === 'A web request received', "Expected title 'A web request received', got: {$signals[0]->title}");
        assert_true($signals[0]->details == ['url' => '/', 'method' => 'POST', 'variables' => []], "Unexpected details for RequestReceived");

        assert_true($signals[1] instanceof RouteDetected);
        assert_true($signals[1]->title === 'Web route detected', "Expected title 'Web route detected', got: {$signals[1]->title}");
        assert_true($signals[1]->details == ['route' => '/'], "Unexpected details for RouteDetected");

        assert_true($signals[2] instanceof DisallowedMethodDetected);
        assert_true($signals[2]->title === 'Disallowed method detected', "Expected title 'Disallowed method detected', got: {$signals[2]->title}");
        assert_true($signals[2]->details == ['route' => '/', 'method' => 'POST', 'allowed_methods' => ['GET', 'DELETE']], "Unexpected details for DisallowedMethodDetected");
    }
);
