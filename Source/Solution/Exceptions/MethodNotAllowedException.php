<?php

namespace PhpRepos\WebRouter\Solution\Exceptions;

use Exception;
use Throwable;

class MethodNotAllowedException extends Exception
{
    public function __construct(
        public readonly string $url,
        public readonly array $allowed_methods = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct("Method is not allowed for URL: {$url}", $code, $previous);
    }
}