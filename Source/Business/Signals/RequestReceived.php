<?php

namespace PhpRepos\WebRouter\Business\Signals;

use PhpRepos\Observer\API\Event;

/**
 * Signal emitted when a web request is received.
 *
 * Fired at the start of request processing with request details.
 */
class RequestReceived extends Event
{
    /**
     * Create a request received signal.
     *
     * @param string $url The request URL
     * @param string $method The HTTP method
     * @param array $variables Request variables ($_GET, $_POST, $_FILES)
     * @return static
     */
    public static function to(string $url, string $method, array $variables): static
    {
        return static::create('A web request received', ['url' => $url, 'method' => $method, 'variables' => $variables]);
    }
}
