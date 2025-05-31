<?php

namespace PhpRepos\Web\Exceptions;

use Exception;
use Throwable;

class MethodNotAllowedException extends Exception
{
    public function __construct(string $url, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct("Method is not allowed for URL: {$url}", $code, $previous);
    }
}