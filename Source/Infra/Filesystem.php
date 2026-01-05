<?php

namespace PhpRepos\WebRouter\Infra\Filesystem;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function getcwd;

/**
 * Recursively list all files in a directory.
 *
 * Traverses the directory tree and returns an array of all file paths.
 * This replaces the file-manager package's ls_all() function.
 *
 * @param string $directory The directory path to search
 * @return array Array of absolute file paths
 */
function list_files(string $directory): array
{
    if (!is_dir($directory)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

/**
 * Get the absolute path of a file or directory.
 *
 * Resolves relative paths, symlinks, and normalizes the path.
 *
 * @param string $path The path to resolve
 * @return string|false The absolute path, or false if the path doesn't exist
 */
function realpath(string $path): string|false
{
    return \realpath($path);
}

/**
 * Get the current working directory (root of the application).
 *
 * @return string The absolute path to the current working directory
 */
function root(): string
{
    return getcwd();
}

/**
 * Join path segments into a single path.
 *
 * Combines a base path with one or more relative path segments,
 * properly handling directory separators.
 *
 * @param string $base The base path
 * @param string ...$segments Additional path segments to join
 * @return string The combined path
 */
function join(string $base, string ...$segments): string
{
    $parts = [$base];

    foreach ($segments as $segment) {
        $segment = trim($segment, '/\\');
        if ($segment !== '') {
            $parts[] = $segment;
        }
    }

    return implode(DIRECTORY_SEPARATOR, $parts);
}
