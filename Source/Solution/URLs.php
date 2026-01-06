<?php

namespace PhpRepos\WebRouter\Solution\URLs;

use function PhpRepos\Logger\API\Logs\debug;

/**
 * Extract the path component from a URL.
 *
 * Parses the URL and returns the path, handling encoded characters
 * except for encoded forward slashes (%2F).
 *
 * @param string $url The URL to parse
 * @return string The URL path component
 */
function path(string $url): string
{
    debug('Extracting path from URL', ['url' => $url]);

    $parsed_url = parse_url($url);
    $url_path = $parsed_url['path'] ?? '/';

    // Decode all except %2F (encoded forward slash)
    return preg_replace_callback('/%(?![2][Ff])[^%]+/', fn ($m) => urldecode($m[0]), $url_path);
}

/**
 * Split a URL path into parts.
 *
 * Splits the path by '/' and removes empty parts from trimming.
 *
 * @param string $url_path The URL path (e.g., '/products/123/orders')
 * @return array Array of URL path parts
 */
function parts(string $url_path): array
{
    return explode('/', trim($url_path, '/'));
}
