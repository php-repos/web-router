<?php

namespace PhpRepos\WebRouter\Business\Signals;

use PhpRepos\Observer\API\Event;

/**
 * Signal emitted when a matching route is found.
 *
 * Fired after successful route pattern matching.
 */
class RouteDetected extends Event
{
    /**
     * Create a route detected signal.
     *
     * @param string $route The matched route pattern
     * @return static
     */
    public static function route(string $route): static
    {
        return static::create('Web route detected', ['route' => $route]);
    }
}
