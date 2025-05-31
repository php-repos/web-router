<?php

namespace PhpRepos\Web\Signals;

use PhpRepos\Observer\Signals\Event;

class RouteNotFoundForUrl extends Event
{
    public static function to(string $url, string $method): static
    {
        return static::create('Route not found for the given URL', ['url' => $url, 'method' => $method]);
    }
}
