<?php

namespace PhpRepos\WebRouter\Business\Signals;

use PhpRepos\Observer\API\Plan;

/**
 * Signal emitted when starting to find a matching route for a URL.
 *
 * Fired before searching through routes to find a match.
 */
class FindingRoute extends Plan
{
    /**
     * Create a route finding plan signal.
     *
     * @param string $url The URL being matched
     * @param int $route_count Number of routes being searched
     * @return static
     */
    public static function for_url(string $url, int $route_count): static
    {
        return static::create('Finding route for URL.', [
            'url' => $url,
            'route_count' => $route_count,
        ]);
    }
}