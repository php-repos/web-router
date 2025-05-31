<?php

use PhpRepos\FileManager\Path;
use function PhpRepos\FileManager\Paths\root;
use function PhpRepos\Web\Routes\detect_routes;
use function PhpRepos\Web\Web\respond;

return respond(
    routes: detect_routes(Path::from(root())->sub()),
    url: $_SERVER['REQUEST_URI'],
    method: $_SERVER['REQUEST_METHOD'],
    variables: array_replace($_GET, $_POST, $_FILES),
);
