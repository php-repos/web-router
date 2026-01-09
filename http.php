<?php

use PhpRepos\WebRouter\Business\Finder;
use PhpRepos\WebRouter\Business\Router;

$outcome = Finder\path(getcwd() . DIRECTORY_SEPARATOR . 'Routes');
if (!$outcome->success) return $outcome->message;

$routes = $outcome->data['routes'];

$outcome = Router\respond(
    routes: $routes,
    url: $_SERVER['REQUEST_URI'],
    method: $_SERVER['REQUEST_METHOD'],
    variables: array_replace($_GET, $_POST, $_FILES),
);
if (!$outcome->success) return $outcome->message;

return $outcome->data['response'];
