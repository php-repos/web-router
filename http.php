<?php

use PhpRepos\WebRouter\Business\Finder;
use PhpRepos\WebRouter\Business\Router;
use PhpRepos\WebRouter\Business\Outcome;

return function (string $routes_path, array $handlers = []): Outcome {
    $handlers['on_success'] ??= function (Outcome $outcome) {
        echo $outcome->data['response'];
    };

    $handlers['on_not_found'] ??= function (Outcome $outcome) {
        http_response_code(404);
        echo $outcome->message;
    };

    $handlers['on_method_not_allowed'] ??= function (Outcome $outcome) {
        http_response_code(405);
        if (!empty($outcome->data['allowed_methods'])) {
            header('Allow: ' . implode(', ', $outcome->data['allowed_methods']));
        }
        echo $outcome->message;
    };

    $handlers['on_validation_error'] ??= function (Outcome $outcome) {
        http_response_code(422);
        echo $outcome->message;
    };

    $handlers['on_internal_error'] ??= function (Outcome $outcome) {
        http_response_code(500);
        echo $outcome->message;
    };

    $outcome = Finder\path($routes_path);
    if (!$outcome->success) {
        $result = new Outcome(false, $outcome->message, [
            'route_not_found' => false,
            'method_not_allowed' => false,
            'validation_error' => false,
            'internal_error' => true,
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'exception' => null,
        ]);

        $handlers['on_internal_error']($result);

        return $result;
    }

    $result = Router\respond(
        routes: $outcome->data['routes'],
        url: $_SERVER['REQUEST_URI'],
        method: $_SERVER['REQUEST_METHOD'],
        variables: array_replace($_GET, $_POST, $_FILES),
    );

    if ($result->success) {
        $handlers['on_success']($result);
    } elseif ($result->data['route_not_found']) {
        $handlers['on_not_found']($result);
    } elseif ($result->data['method_not_allowed']) {
        $handlers['on_method_not_allowed']($result);
    } elseif ($result->data['validation_error']) {
        $handlers['on_validation_error']($result);
    } else {
        $handlers['on_internal_error']($result);
    }

    return $result;
};
