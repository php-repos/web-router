<?php

namespace PhpRepos\WebRouter\Business\Signals;

use PhpRepos\Observer\API\Event;

/**
 * Signal emitted when route finding fails to find a match.
 *
 * Fired when no route matches the given URL after searching all routes.
 */
class RouteFindingFailed extends Event
{
    /**
     * Create a route finding failed signal.
     *
     * @param string $url The URL that failed to match
     * @param int $route_count Number of routes that were searched
     * @return static
     */
    public static function for_url(string $url, int $route_count): static
    {
        return static::create('Route finding failed for URL.', [
            'url' => $url,
            'route_count' => $route_count,
        ]);
    }
}