<?php

namespace PhpRepos\WebRouter\Business\Signals;

use PhpRepos\Observer\API\Plan;

/**
 * Signal emitted before executing a route handler.
 *
 * Fired after validation, just before handler execution.
 */
class ExecutingHandler extends Plan
{
    /**
     * Create an executing handler signal.
     *
     * @param string $route The route pattern being executed
     * @param array $params The prepared parameters for the handler
     * @return static
     */
    public static function using(string $route, array $params): static
    {
        return static::create('Executing the route handler', ['route' => $route, 'params' => $params]);
    }
}
