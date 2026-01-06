<?php

namespace PhpRepos\WebRouter\Business\Signals;

use PhpRepos\Observer\API\Event;

/**
 * Signal emitted when no route matches the request URL.
 *
 * Fired when route matching fails.
 */
class RouteNotFoundForUrl extends Event
{
    /**
     * Create a route not found signal.
     *
     * @param string $url The requested URL that wasn't matched
     * @param string $method The HTTP method
     * @return static
     */
    public static function to(string $url, string $method): static
    {
        return static::create('Route not found for the given URL', ['url' => $url, 'method' => $method]);
    }
}
