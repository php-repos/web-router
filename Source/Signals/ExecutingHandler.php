<?php

namespace PhpRepos\Web\Signals;

use PhpRepos\Observer\Signals\Plan;

class ExecutingHandler extends Plan
{
    public static function using(string $route, array $params): static
    {
        return static::create('Executing the route handler', ['route' => $route, 'params' => $params]);
    }
}
