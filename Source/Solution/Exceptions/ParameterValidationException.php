<?php

namespace PhpRepos\WebRouter\Solution\Exceptions;

use Exception;
use Throwable;

class ParameterValidationException extends Exception
{
    public function __construct(string $param, string $expected_type, string $actual_type, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct("Expected type $expected_type for $param but $actual_type provided.", $code, $previous);
    }
}