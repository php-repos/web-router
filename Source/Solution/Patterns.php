<?php

namespace PhpRepos\WebRouter\Solution\Patterns;

use PhpRepos\WebRouter\Solution\URLs;
use PhpRepos\WebRouter\Infra\Arrays;
use PhpRepos\WebRouter\Infra\Strings;

/**
 * Get all optional parameters from a pattern.
 *
 * Optional parameters are denoted by [{param}] syntax.
 *
 * @param string $pattern The route pattern
 * @return array Array of optional parameter parts
 */
function optionals(string $pattern): array
{
    $pattern_parts = URLs\parts($pattern);

    return Arrays\filter($pattern_parts, fn ($part) => is_optional($part));
}

/**
 * Check if a pattern part is an optional parameter.
 *
 * Optional parameters use the syntax: [{param}]
 *
 * @param string $part The pattern part to check
 * @return bool True if optional parameter, false otherwise
 */
function is_optional(string $part): bool
{
    return Strings\starts_with($part, '[{') && Strings\ends_with($part, '}]');
}

/**
 * Check if a pattern part is a dynamic parameter.
 *
 * Dynamic parameters use the syntax: {param} (but not [{param}])
 *
 * @param string $part The pattern part to check
 * @return bool True if dynamic parameter, false otherwise
 */
function is_dynamic(string $part): bool
{
    return is_optional($part) || (Strings\starts_with($part, '{') && Strings\ends_with($part, '}'));
}

/**
 * Extract the variable name from a pattern part.
 *
 * Handles both dynamic {param} and optional [{param}] syntax.
 *
 * @param string $part The pattern part (e.g., '{id}' or '[{category}]')
 * @return string The variable name (e.g., 'id' or 'category')
 */
function variable_name(string $part): string
{
    if (is_optional($part)) {
        return substr($part, 2, -2);
    }

    return substr($part, 1, -1);
}

/**
 * Check if a URL path matches a route pattern structure.
 *
 * Validates that the URL has a compatible number of parts with the pattern,
 * considering optional parameters. This is a preliminary check before
 * detailed pattern matching.
 *
 * @param string $pattern The route pattern to match against
 * @param string $url_path The URL path to check
 * @return bool True if the URL could potentially match the pattern structure
 */
function matches(string $pattern, string $url_path): bool
{
    $url_parts = URLs\parts($url_path);
    $pattern_parts = URLs\parts($pattern);
    $optional_count = count(optionals($pattern));

    if (count($url_parts) === count($pattern_parts)) return true;
    if ($optional_count === 0 && count($url_parts) < count($pattern_parts)) return false;

    return count($url_parts) <= count($pattern_parts) - $optional_count;
}
