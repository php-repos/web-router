<?php

namespace PhpRepos\WebRouter\Infra\Reflections;

use ReflectionException;
use ReflectionFunction;

/**
 * Get the parameters of a callable.
 *
 * Uses reflection to extract parameter information from a callable.
 * Returns an array of ReflectionParameter objects.
 *
 * @param callable $handler The callable to inspect
 * @return array Array of ReflectionParameter objects
 * @throws ReflectionException
 */
function get_parameters(callable $handler): array
{
    $reflection = new ReflectionFunction($handler);
    return $reflection->getParameters();
}

/**
 * Get attributes of a specific class from a callable.
 *
 * Uses reflection to extract all attributes of the specified class
 * attached to the callable.
 *
 * @param callable $handler The callable to inspect
 * @param string $attribute_class The fully qualified attribute class name
 * @return array Array of ReflectionAttribute objects
 * @throws ReflectionException
 */
function get_attributes(callable $handler, string $attribute_class): array
{
    $reflection = new ReflectionFunction($handler);
    return $reflection->getAttributes($attribute_class, \ReflectionAttribute::IS_INSTANCEOF);
}
