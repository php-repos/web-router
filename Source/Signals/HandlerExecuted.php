<?php

namespace PhpRepos\Web\Signals;

use PhpRepos\Observer\Signals\Event;

class HandlerExecuted extends Event
{
    public static function with_response(mixed $response, string $route): static
    {
        return static::create('Route handler finished execution', ['response' => $response, 'route' => $route]);
    }
}
