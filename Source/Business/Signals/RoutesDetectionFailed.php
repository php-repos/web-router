<?php

namespace PhpRepos\WebRouter\Business\Signals;

use PhpRepos\Observer\API\Event;

/**
 * Signal emitted when routes detection fails.
 *
 * Fired when an error occurs during directory scanning or route loading.
 */
class RoutesDetectionFailed extends Event
{
    /**
     * Create a routes detection failed signal.
     *
     * @param string $directory The routes directory that failed to scan
     * @param string $error The error message describing the failure
     * @return static
     */
    public static function with_error(string $directory, string $error): static
    {
        return static::create('Routes detection failed.', [
            'directory' => $directory,
            'error' => $error,
        ]);
    }
}