<?php

namespace PhpRepos\Web\Routes;

use PhpRepos\FileManager\Path;
use PhpRepos\Web\Router;
use function PhpRepos\Datatype\Arr\filter;
use function PhpRepos\Datatype\Arr\reduce;
use function PhpRepos\Datatype\Str\before_last_occurrence;
use function PhpRepos\Datatype\Str\replace_first_occurrence;
use function PhpRepos\FileManager\Directories\ls_all;

function detect_routes(Path $root): Router
{
    $files = filter(ls_all($root), fn (string $path) => is_file($path));

    return reduce($files, function (Router $router, string $path) use ($root) {
        $handler = require $path;
        $handler = is_callable($handler) ? $handler : fn () => $handler;

        return $router->handle(
            url_pattern: replace_first_occurrence(str_ends_with($path, 'index.php')
                ? before_last_occurrence($path, 'index.php')
                : before_last_occurrence($path, '.php'), $root, ''),
            handler: $handler
        );
    }, new Router());
}
