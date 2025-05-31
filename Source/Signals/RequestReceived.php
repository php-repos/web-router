<?php

namespace PhpRepos\Web\Signals;

use PhpRepos\Observer\Signals\Event;

class RequestReceived extends Event
{
    public static function to(string $url, string $method, array $variables): static
    {
        return static::create('A web request received', ['url' => $url, 'method' => $method, 'variables' => $variables]);
    }
}
