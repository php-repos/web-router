<?php

namespace PhpRepos\Web\Signals;

use PhpRepos\Observer\Signals\Event;

class RouteDetected extends Event
{
    public static function route(string $route): static
    {
        return static::create('Web route detected', ['route' => $route]);
    }
}
