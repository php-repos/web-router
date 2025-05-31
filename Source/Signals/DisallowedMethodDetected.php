<?php

namespace PhpRepos\Web\Signals;

use PhpRepos\Observer\Signals\Signal;

class DisallowedMethodDetected extends Signal
{
    public static function for(string $route, string $method, array $allowed_methods): static
    {
        return static::create('Disallowed method detected', ['route' => $route, 'method' => $method, 'allowed_methods' => $allowed_methods]);
    }
}
