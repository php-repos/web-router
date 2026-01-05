<?php

namespace PhpRepos\WebRouter\Business\Finder;

use PhpRepos\WebRouter\Business\Outcome;
use PhpRepos\WebRouter\Business\Signals\DetectingRoutes;
use PhpRepos\WebRouter\Business\Signals\RoutesDetectionCompleted;
use PhpRepos\WebRouter\Business\Signals\RoutesDetectionFailed;
use PhpRepos\WebRouter\Solution\Paths;
use PhpRepos\WebRouter\Solution\Routes;
use Throwable;
use function PhpRepos\Observer\API\Bus\send;

/**
 * Detect and load routes from a directory structure.
 *
 * Scans the given routes directory for PHP files and converts them
 * into route definitions with URL patterns and handlers. Supports
 * dynamic parameters in filenames (e.g., {id}, {?optional}) and
 * automatically generates URL patterns based on the file structure.
 *
 * Specification:
 * 1. Resolve the routes directory path from the project root
 * 2. Find all PHP files in the directory recursively
 * 3. For each file:
 *    - Load the file and extract the handler (callable or return value)
 *    - Generate URL pattern from the file path relative to routes directory
 *    - Convert dynamic filename parameters to route parameters
 *    - Create route definition with pattern and handler
 * 4. Return success outcome with all routes, or failure outcome with error
 *
 * Example directory structure:
 * Routes/
 * ├── index.php              -> pattern: "/"
 * ├── users/
 * │   ├── index.php          -> pattern: "/users"
 * │   ├── {id}/
 * │   │   ├── show.php       -> pattern: "/users/{id}/show"
 * │   │   ├── delete.php     -> pattern: "/users/{id}"
 * │   ├── create.php         -> pattern: "/users/create"
 * │   ├── {email}/
 * │   │   ├── {id?}.php      -> pattern: "/users/{email}/{id?}"
 *
 * @param string $routes_directory Path to the routes directory relative to project root
 * @param string $suffix File extension to look for (default: '.php')
 * @return Outcome Success with ['routes' => array] or failure with error message
 * @throws Throwable When filesystem operations or file loading fails
 */
function path(string $routes_directory, string $suffix = '.php'): Outcome
{
    send(DetectingRoutes::from_directory($routes_directory, $suffix));

    try {
        $root = Paths\from_root($routes_directory);
        $files = Paths\all_routes($root, $suffix);
        $routes = [];

        foreach ($files as $file) {
            $handler = require $file;
            $routes[] = [
                'pattern' => Routes\url_pattern($root, $file, $suffix),
                'handler' => is_callable($handler) ? $handler : fn () => $handler,
            ];
        }

        send(RoutesDetectionCompleted::successfully($routes_directory, count($routes)));

        return new Outcome(true, 'Routes loaded successfully', [
            'routes' => $routes,
        ]);
    } catch (Throwable $e) {
        send(RoutesDetectionFailed::with_error($routes_directory, $e->getMessage()));

        return new Outcome(false, "Failed to load routes: {$e->getMessage()}", []);
    }
}
