<?php

namespace PhpRepos\Web\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_FUNCTION|Attribute::IS_REPEATABLE)]
class Method
{
    public function __construct(public string $method) {}
}
