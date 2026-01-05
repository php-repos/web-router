<?php

namespace PhpRepos\WebRouter\Business\Signals;

use PhpRepos\Observer\API\Plan;

/**
 * Signal emitted when starting to detect routes from a directory.
 *
 * Fired before scanning the routes directory for PHP files.
 */
class DetectingRoutes extends Plan
{
    /**
     * Create a route detection plan signal.
     *
     * @param string $directory The routes directory being scanned
     * @param string $suffix The file suffix being looked for
     * @return static
     */
    public static function from_directory(string $directory, string $suffix): static
    {
        return static::create('Detecting routes from directory.', [
            'directory' => $directory,
            'suffix' => $suffix,
        ]);
    }
}