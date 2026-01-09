<?php

namespace PhpRepos\WebRouter\Infra\Filesystem;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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