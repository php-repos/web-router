<?php

namespace PhpRepos\WebRouter\Business\Signals;

use PhpRepos\Observer\API\Event;

/**
 * Signal emitted when routes are successfully detected and loaded.
 *
 * Fired after successfully scanning the directory and creating route definitions.
 */
class RoutesDetectionCompleted extends Event
{
    /**
     * Create a routes detection completed signal.
     *
     * @param string $directory The routes directory that was scanned
     * @param int $route_count Number of routes detected
     * @return static
     */
    public static function successfully(string $directory, int $route_count): static
    {
        return static::create('Routes detection completed successfully.', [
            'directory' => $directory,
            'route_count' => $route_count,
        ]);
    }
}