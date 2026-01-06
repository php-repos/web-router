<?php

namespace PhpRepos\WebRouter\Business;

/**
 * Represents the outcome of a business operation.
 *
 * Provides a consistent structure for operation results across the
 * Business layer. All Business functions should return an Outcome.
 *
 * @property-read bool $success Whether the operation succeeded
 * @property-read string $message Human-readable message about the outcome
 * @property-read array $data Additional data payload from the operation
 */
readonly class Outcome
{
    public function __construct(public bool $success, public string $message, public array $data) {}
}
