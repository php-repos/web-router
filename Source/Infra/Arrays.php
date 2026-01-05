<?php

namespace PhpRepos\WebRouter\Infra\Arrays;

/**
 * Filter an array using a callback function.
 *
 * Returns a new array containing only the elements for which the callback
 * returns true. This replaces the datatype package's Arr\filter() function.
 *
 * @param array $items The array to filter
 * @param callable $callback Filter function that receives each item and returns bool
 * @return array Filtered array with preserved keys
 */
function filter(array $items, callable $callback): array
{
    return array_filter($items, $callback);
}

/**
 * Reduce an array to a single value using a callback function.
 *
 * Iteratively applies the callback to reduce the array to a single value.
 * This replaces the datatype package's Arr\reduce() function.
 *
 * @param array $items The array to reduce
 * @param callable $callback Reducer function that receives (carry, item) and returns new carry value
 * @param mixed $initial Initial value for the reduction
 * @return mixed The final reduced value
 */
function reduce(array $items, callable $callback, mixed $initial): mixed
{
    return array_reduce($items, $callback, $initial);
}

function sort(array $items, callable $comparator): array
{
    usort($items, $comparator);
    return $items;
}
