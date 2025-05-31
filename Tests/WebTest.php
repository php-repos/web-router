<?php

use PhpRepos\Datatype\Arr;
use PhpRepos\Datatype\Str;
use PhpRepos\FileManager\Path;
use PhpRepos\Observer\Signals\Internals\HandlerExecution;
use PhpRepos\Observer\Signals\Internals\HandlerFound;
use PhpRepos\Observer\Signals\Internals\NoHandlerFound;
use PhpRepos\Observer\Signals\Signal;
use PhpRepos\Web\Attributes\Method;
use PhpRepos\Web\Exceptions\MethodNotAllowedException;
use PhpRepos\Web\Exceptions\ParameterValidationException;
use PhpRepos\Web\Exceptions\RouteNotFoundException;
use PhpRepos\Web\Router;
use PhpRepos\Web\Signals\HandlerExecuted;
use PhpRepos\Web\Signals\DisallowedMethodDetected;
use PhpRepos\Web\Signals\RouteDetected;
use PhpRepos\Web\Signals\RouteNotFoundForUrl;
use PhpRepos\Web\Signals\ExecutingHandler;
use PhpRepos\Web\Signals\RequestReceived;
use function PhpRepos\Observer\Observer\subscribe;
use function PhpRepos\TestRunner\Assertions\assert_true;
use function PhpRepos\TestRunner\Assertions\assert_false;
use function PhpRepos\TestRunner\Runner\test;
use function PhpRepos\Web\Routes\detect_routes;
use function PhpRepos\Web\Web\respond;

$test_routes = detect_routes(Path::from(__DIR__)->sub( 'Routes'));

test(
    title: 'it should use index.php when there is no other file to handle GET request for home',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/', 'GET');

        assert_true('Hello World!' === $response, 'Did not return expected response: ' . $response);
    }
);

test(
    title: 'it should throw route not found when there is no handler for the given URL',
    case: function () use ($test_routes) {
        try {
            respond($test_routes, '/not-found', 'GET');
            assert_false(true, 'It should not reach to this point');
        } catch (RouteNotFoundException $exception) {
            assert_true('Route not found for URL: /not-found' === $exception->getMessage(), 'The exception message is not what we expect: ' . $exception->getMessage());
        }
    }
);

test(
    title: 'it should use index.php for any method when not specified',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/', 'POST');

        assert_true('Hello World!' === $response, 'Did not return expected response: ' . $response);
    }
);

test(
    title: 'it should use index.php when there is sub urls',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/users', 'GET');

        assert_true('List of users' === $response, 'Did not return expected response: ' . $response);
    }
);

test(
    title: 'it should use index.php when there is sub urls having trailing /',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/users/', 'GET');

        assert_true('List of users' === $response, 'Did not return expected response: ' . $response);
    }
);

test(
    title: 'it should throw method not allowed, when the index does not support the method',
    case: function () use ($test_routes) {
        try {
            respond($test_routes, '/users', 'POST');
            assert_false(true, 'It should not reach to this point');
        } catch (MethodNotAllowedException $exception) {
            assert_true('Method is not allowed for URL: /users' === $exception->getMessage(), 'The exception message is not what we expect: ' . $exception->getMessage());
        }
    }
);

test(
    title: 'it should use method file when there is a file for method',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/v1/posts/get', 'GET');

        assert_true('List of posts' === $response, 'Did not return expected response: ' . $response);
    }
);

test(
    title: 'it should use method file when there is a file for method and the URL has a trailing /',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/v1/posts/get/', 'GET');

        assert_true('List of posts' === $response, 'Did not return expected response: ' . $response);
    }
);

test(
    title: 'it should find the router when there is encoded space',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/v1/posts/search%20by', 'GET');

        assert_true('Search on posts' === $response, 'Did not return expected response: ' . $response);
    }
);

test(
    title: 'it should pass route parameters to the handler',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/users/123/show', 'GET');

        assert_true('Showing user 123' === $response, 'Did not return expected response: ' . $response);
    }
);

test(
    title: 'it should pass route parameters to the handler when it is defined on the handler filename',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/v1/posts/123', 'GET');

        assert_true('Showing post 123' === $response, 'Did not return expected response: ' . $response);
    }
);

test(
    title: 'it should find the handler with different cases',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/USERS/123/posts', 'GET');

        assert_true('Showing posts belong to user 123' === $response, 'Did not return expected response: ' . $response);
    }
);

test(
    title: 'it should pass multiple route parameters',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/users/123/posts/456', 'GET');

        assert_true('Showing post 456 that belongs to 123' === $response, 'Did not return expected response: ' . $response);
    }
);

test(
    title: 'it should use method file when there is a file for any number of methods',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/users/123/edit', 'PATCH');

        assert_true('Edit user 123' === $response, 'Did not return expected response: ' . $response);

        $response = respond($test_routes, '/users/123/edit', 'PUT');

        assert_true('Edit user 123' === $response, 'Did not return expected response: ' . $response);
    }
);

test(
    title: 'it should handle encoded / as parameters',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/users/123%2F456/posts/456', 'GET');
        assert_true('Showing post 456 that belongs to 123/456' === $response, 'Did not return expected response: ' . $response);
    }
);

test(
    title: 'it should not use .. for path change',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/users/../posts/456', 'GET');
        assert_true('Showing post 456 that belongs to ..' === $response, 'Did not return expected response: ' . $response);
    }
);

test(
    title: 'it should return whatever the handler file returns',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/specific', 'GET');
        assert_true(['Hello' => 'World'] === $response, 'Did not return expected response');
    }
);

test(
    title: 'it should handle optional parameters',
    case: function () use ($test_routes) {
         $response = respond($test_routes, '/products/cars', 'GET');
         assert_true('Showing products in category cars' === $response, 'Did not return expected response when optional param passed: ' . $response);

        $response = respond($test_routes, '/products', 'GET');
        assert_true('Showing products in category All' === $response, 'Did not return expected response when optional parameter is absent: ' . $response);
    }
);

test(
    title: 'it should find proper match when there are similar optional routes',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/products/type', 'GET');
        assert_true('Get products group by types' === $response, 'Did not return expected responses: ' . $response);
    }
);

test(
    title: 'it should handle empty URL by defaulting to root handler',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '', 'GET');
        assert_true('Hello World!' === $response, 'Did not return expected response for empty URL: ' . $response);
    }
);

test(
    title: 'it should normalize multiple leading slashes to root',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '////', 'GET');
        assert_true('Hello World!' === $response, 'Did not normalize multiple slashes: ' . $response);
    }
);

test(
    title: 'it should ignore query strings and route correctly',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/users/123/show?format=json', 'GET');
        assert_true('Showing user 123' === $response, 'Did not ignore query string: ' . $response);
    }
);

test(
    title: 'it should throw validation error when the type is not what we want',
    case: function () use ($test_routes) {
        try {
            respond($test_routes, '/users/not_valid/delete', 'DELETE');
            assert_false(true, 'it should not pass validation!');
        } catch (ParameterValidationException $exception) {
            assert_true('Expected type int for user_id but string provided.' === $exception->getMessage(), 'Message is not what we expected: ' . $exception->getMessage());
        }
    }
);

test(
    title: 'it should pass null for optional params when there is no value, and use optional value when it is defined',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/posts/123', 'GET');

        assert_true("Optional post 123 with title null" === $response, 'Response is not expected: ' . $response);
    },
);

test(
    title: 'it should pass handle bool for params',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/posts/123/true', 'DELETE');
        assert_true("Delete post ID 123 with force as true" === $response, 'Response is not expected for true: ' . $response);

        $response = respond($test_routes, '/posts/123/false', 'DELETE');
        assert_true("Delete post ID 123 with force as false" === $response, 'Response is not expected for false: ' . $response);

        $response = respond($test_routes, '/posts/123/1', 'DELETE');
        assert_true("Delete post ID 123 with force as true" === $response, 'Response is not expected for 1: ' . $response);

        $response = respond($test_routes, '/posts/123/0', 'DELETE');
        assert_true("Delete post ID 123 with force as false" === $response, 'Response is not expected for 0: ' . $response);

        try {
            $response = respond($test_routes, '/posts/123/not_true', 'DELETE');
            assert_false(true, 'It should not parse the given value!');
        } catch (ParameterValidationException $exception) {
            assert_true('Expected type bool for force but string provided.' === $exception->getMessage(), 'Message is not what we expected: ' . $exception->getMessage());
        }
    },
);

test(
    title: 'it should pass variables from the given variables',
    case: function () use ($test_routes) {
        $response = respond($test_routes, '/posts/create', 'POST', ['title' => 'Post title', 'image' => ['filename' => 'filename.png', 'extension' => 'jpg']]);

        assert_true('Post with title Post title and description null and image filename.png-jpg has been created.' === $response, 'Respond does not match: ' . $response);
    }
);

test(
    title: 'it should send specific signals when there is a match and response',
    case: function () {
        $signals = [];

        subscribe(function (Signal $signal) use (&$signals) {
            if (!$signal instanceof HandlerExecution && !$signal instanceof HandlerFound && !$signal instanceof NoHandlerFound) {
                $signals[] = $signal;
            }
        });

        $router = Router::from([])->handle('/', fn () => 'hello world');

        $response = respond($router, '/', 'GET');

        assert_true($response === 'hello world');

        assert_true($signals[0] instanceof RequestReceived);
        Str\assert_equal($signals[0]->title, 'A web request received');
        Arr\assert_equal($signals[0]->details, ['url' => '/', 'method' => 'GET', 'variables' => []]);

        assert_true($signals[1] instanceof RouteDetected);
        Str\assert_equal($signals[1]->title, 'Web route detected');
        Arr\assert_equal($signals[1]->details, ['route' => '/']);

        assert_true($signals[2] instanceof ExecutingHandler);
        Str\assert_equal($signals[2]->title, 'Executing the route handler');
        Arr\assert_equal($signals[2]->details, ['route' => '/', 'params' => []]);

        assert_true($signals[3] instanceof HandlerExecuted);
        Str\assert_equal($signals[3]->title, 'Route handler finished execution');
        Arr\assert_equal($signals[3]->details, ['response' => 'hello world', 'route' => '/']);
    }
);

test(
    title: 'it should send specific signals when route not detected',
    case: function () {
        $signals = [];

        subscribe(function (Signal $signal) use (&$signals) {
            if (!$signal instanceof HandlerExecution && !$signal instanceof HandlerFound && !$signal instanceof NoHandlerFound) {
                $signals[] = $signal;
            }
        });

        $router = Router::from([]);

        try {
            respond($router, '/', 'GET');
        } catch (Exception $exception) {
            assert_true($exception instanceof RouteNotFoundException);
        }

        assert_true($signals[0] instanceof RequestReceived);
        Str\assert_equal($signals[0]->title, 'A web request received');
        Arr\assert_equal($signals[0]->details, ['url' => '/', 'method' => 'GET', 'variables' => []]);

        assert_true($signals[1] instanceof RouteNotFoundForUrl);
        Str\assert_equal($signals[1]->title, 'Route not found for the given URL');
        Arr\assert_equal($signals[1]->details, ['url' => '/', 'method' => 'GET']);
    }
);

test(
    title: 'it should send specific signals when route detects but method is not allowed',
    case: function () {
        $signals = [];

        subscribe(function (Signal $signal) use (&$signals) {
            if (!$signal instanceof HandlerExecution && !$signal instanceof HandlerFound && !$signal instanceof NoHandlerFound) {
                $signals[] = $signal;
            }
        });

        $router = Router::from([])->handle('/', #[Method('GET'), Method('DELETE')]fn () => 'hello world');

        try {
            respond($router, '/', 'POST');
        } catch (Exception $exception) {
            assert_true($exception instanceof MethodNotAllowedException);
        }

        assert_true($signals[0] instanceof RequestReceived);
        Str\assert_equal($signals[0]->title, 'A web request received');
        Arr\assert_equal($signals[0]->details, ['url' => '/', 'method' => 'POST', 'variables' => []]);

        assert_true($signals[1] instanceof RouteDetected);
        Str\assert_equal($signals[1]->title, 'Web route detected');
        Arr\assert_equal($signals[1]->details, ['route' => '/']);

        assert_true($signals[2] instanceof DisallowedMethodDetected);
        Str\assert_equal($signals[2]->title, 'Disallowed method detected');
        Arr\assert_equal($signals[2]->details, ['route' => '/', 'method' => 'POST', 'allowed_methods' => ['GET', 'DELETE']]);
    }
);
