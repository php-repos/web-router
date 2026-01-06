<?php

namespace PhpRepos\WebRouter\Business\Signals;

use PhpRepos\Observer\API\Event;

/**
 * Signal emitted after a route handler completes execution.
 *
 * Fired when handler execution finishes successfully.
 */
class HandlerExecuted extends Event
{
    /**
     * Create a handler executed signal.
     *
     * @param mixed $response The response returned by the handler
     * @param string $route The route pattern that was executed
     * @return static
     */
    public static function with_response(mixed $response, string $route): static
    {
        return static::create('Route handler finished execution', ['response' => $response, 'route' => $route]);
    }
}
