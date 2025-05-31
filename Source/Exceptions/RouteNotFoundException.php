<?php

namespace PhpRepos\Web\Exceptions;

use Exception;
use Throwable;

class RouteNotFoundException extends Exception
{
    public function __construct(string $url, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct("Route not found for URL: {$url}", $code, $previous);
    }
}