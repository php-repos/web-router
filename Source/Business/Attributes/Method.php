<?php

namespace PhpRepos\WebRouter\Business\Attributes;

use Attribute;

/**
 * Attribute to specify allowed HTTP methods for a route handler.
 *
 * Can be applied multiple times to allow multiple methods.
 *
 * @example
 * #[Method('GET')]
 * function index() { ... }
 *
 * @example
 * #[Method('GET'), Method('POST')]
 * function handler() { ... }
 */
#[Attribute(Attribute::TARGET_FUNCTION|Attribute::IS_REPEATABLE)]
class Method
{
    public function __construct(public string $method) {}
}
