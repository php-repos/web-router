<?php

use PhpRepos\WebRouter\Business\Outcome;
use PhpRepos\WebRouter\Infra\Filesystem;
use function PhpRepos\TestRunner\Assertions\assert_true;
use function PhpRepos\TestRunner\Assertions\assert_false;
use function PhpRepos\TestRunner\Runner\test;

$handle = require Filesystem\realpath(__DIR__ . '/../http.php');
$routes_path = Filesystem\realpath(__DIR__ . '/Routes');

test(
    title: 'it should call on_success handler when route matches',
    case: function () use ($handle, $routes_path) {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $called = false;
        $captured_outcome = null;

        $outcome = $handle($routes_path, [
            'on_success' => function (Outcome $outcome) use (&$called, &$captured_outcome) {
                $called = true;
                $captured_outcome = $outcome;
            },
        ]);

        assert_true($called, 'on_success handler was not called');
        assert_true($outcome->success, 'Expected successful outcome');
        assert_true($captured_outcome->data['response'] === 'Hello World!', 'Unexpected response: ' . $captured_outcome->data['response']);
        assert_false($outcome->data['route_not_found'], 'route_not_found should be false');
        assert_false($outcome->data['method_not_allowed'], 'method_not_allowed should be false');
        assert_false($outcome->data['validation_error'], 'validation_error should be false');
        assert_false($outcome->data['internal_error'], 'internal_error should be false');
    }
);

test(
    title: 'it should call on_not_found handler when route does not exist',
    case: function () use ($handle, $routes_path) {
        $_SERVER['REQUEST_URI'] = '/non-existent-route';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $called = false;
        $captured_outcome = null;

        $outcome = $handle($routes_path, [
            'on_not_found' => function (Outcome $outcome) use (&$called, &$captured_outcome) {
                $called = true;
                $captured_outcome = $outcome;
            },
        ]);

        assert_true($called, 'on_not_found handler was not called');
        assert_false($outcome->success, 'Expected failure outcome');
        assert_true($outcome->data['route_not_found'], 'route_not_found should be true');
        assert_false($outcome->data['method_not_allowed'], 'method_not_allowed should be false');
        assert_false($outcome->data['validation_error'], 'validation_error should be false');
        assert_false($outcome->data['internal_error'], 'internal_error should be false');
        assert_true($captured_outcome->data['url'] === '/non-existent-route', 'URL should be available in outcome');
    }
);

test(
    title: 'it should call on_method_not_allowed handler when method is not allowed',
    case: function () use ($handle, $routes_path) {
        $_SERVER['REQUEST_URI'] = '/users';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $called = false;
        $captured_outcome = null;

        $outcome = $handle($routes_path, [
            'on_method_not_allowed' => function (Outcome $outcome) use (&$called, &$captured_outcome) {
                $called = true;
                $captured_outcome = $outcome;
            },
        ]);

        assert_true($called, 'on_method_not_allowed handler was not called');
        assert_false($outcome->success, 'Expected failure outcome');
        assert_false($outcome->data['route_not_found'], 'route_not_found should be false');
        assert_true($outcome->data['method_not_allowed'], 'method_not_allowed should be true');
        assert_false($outcome->data['validation_error'], 'validation_error should be false');
        assert_false($outcome->data['internal_error'], 'internal_error should be false');
        assert_true(isset($captured_outcome->data['allowed_methods']), 'allowed_methods should be available');
    }
);

test(
    title: 'it should call on_validation_error handler when parameter validation fails',
    case: function () use ($handle, $routes_path) {
        $_SERVER['REQUEST_URI'] = '/users/not_valid/delete';
        $_SERVER['REQUEST_METHOD'] = 'DELETE';

        $called = false;
        $captured_outcome = null;

        $outcome = $handle($routes_path, [
            'on_validation_error' => function (Outcome $outcome) use (&$called, &$captured_outcome) {
                $called = true;
                $captured_outcome = $outcome;
            },
        ]);

        assert_true($called, 'on_validation_error handler was not called');
        assert_false($outcome->success, 'Expected failure outcome');
        assert_false($outcome->data['route_not_found'], 'route_not_found should be false');
        assert_false($outcome->data['method_not_allowed'], 'method_not_allowed should be false');
        assert_true($outcome->data['validation_error'], 'validation_error should be true');
        assert_false($outcome->data['internal_error'], 'internal_error should be false');
        assert_true(str_contains($captured_outcome->message, 'int'), 'Message should mention expected type');
    }
);

test(
    title: 'it should call on_not_found when routes path is empty or non-existent',
    case: function () use ($handle) {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $called = false;
        $captured_outcome = null;

        $outcome = $handle('/non/existent/path', [
            'on_not_found' => function (Outcome $outcome) use (&$called, &$captured_outcome) {
                $called = true;
                $captured_outcome = $outcome;
            },
        ]);

        assert_true($called, 'on_not_found handler was not called');
        assert_false($outcome->success, 'Expected failure outcome');
        assert_true($outcome->data['route_not_found'], 'route_not_found should be true');
    }
);

test(
    title: 'it should use default handlers when no custom handlers provided',
    case: function () use ($handle, $routes_path) {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        ob_start();
        $outcome = $handle($routes_path);
        $output = ob_get_clean();

        assert_true($outcome->success, 'Expected successful outcome');
        assert_true($output === 'Hello World!', 'Default handler should echo response: ' . $output);
    }
);

test(
    title: 'it should return outcome for further processing',
    case: function () use ($handle, $routes_path) {
        $_SERVER['REQUEST_URI'] = '/users/123/show';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $outcome = $handle($routes_path, [
            'on_success' => function (Outcome $outcome) {},
        ]);

        assert_true($outcome instanceof Outcome, 'Should return Outcome instance');
        assert_true($outcome->success, 'Expected successful outcome');
        assert_true($outcome->data['response'] === 'Showing user 123', 'Response should be available: ' . $outcome->data['response']);
    }
);

test(
    title: 'it should handle query strings correctly',
    case: function () use ($handle, $routes_path) {
        $_SERVER['REQUEST_URI'] = '/users/123/show?format=json&sort=name';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $called = false;

        $outcome = $handle($routes_path, [
            'on_success' => function (Outcome $outcome) use (&$called) {
                $called = true;
            },
        ]);

        assert_true($called, 'on_success handler should be called');
        assert_true($outcome->success, 'Should route correctly ignoring query string');
        assert_true($outcome->data['response'] === 'Showing user 123', 'Response should match');
    }
);
