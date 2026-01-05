<?php

namespace PhpRepos\WebRouter\Business\Signals;

use PhpRepos\Observer\API\Event;

/**
 * Signal emitted when an HTTP method is not allowed for a route.
 *
 * Fired when method validation fails for a matched route.
 */
class DisallowedMethodDetected extends Event
{
    /**
     * Create a disallowed method detected signal.
     *
     * @param string $route The route pattern
     * @param string $method The disallowed HTTP method that was attempted
     * @param array $allowed_methods The methods that are allowed for this route
     * @return static
     */
    public static function for(string $route, string $method, array $allowed_methods): static
    {
        return static::create('Disallowed method detected', ['route' => $route, 'method' => $method, 'allowed_methods' => $allowed_methods]);
    }
}
