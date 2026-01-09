<?php

namespace PhpRepos\WebRouter\Solution\Paths;

use PhpRepos\WebRouter\Infra\Arrays;
use PhpRepos\WebRouter\Infra\Filesystem;
use function PhpRepos\Logger\API\Logs\debug;

/**
 * Get all route files from a directory.
 *
 * Recursively scans the directory and returns all files matching the suffix
 * that are valid route files.
 *
 * @param string $root The root directory to scan
 * @param string $suffix The file suffix to match (e.g., '.php')
 * @return array Array of absolute file paths
 */
function all_routes(string $root, string $suffix): array
{
    debug('Finding all route files', ['root' => $root, 'suffix' => $suffix]);

    return Arrays\filter(Filesystem\list_files($root), fn (string $path) => str_ends_with($path, $suffix) && is_file($path));
}
