<?php

namespace PhpRepos\WebRouter\Infra\Strings;

/**
 * Get the substring before the last occurrence of a search string.
 *
 * Returns the portion of the string before the last occurrence of the
 * search string. This replaces the datatype package's Str\before_last_occurrence() function.
 *
 * @param string $string The string to search in
 * @param string $search The substring to search for
 * @return string The portion before the last occurrence, or the original string if not found
 */
function before_last_occurrence(string $string, string $search): string
{
    $position = strrpos($string, $search);

    if ($position === false) {
        return $string;
    }

    return substr($string, 0, $position);
}

/**
 * Replace the first occurrence of a search string with a replacement.
 *
 * Replaces only the first occurrence of the search string. This replaces
 * the datatype package's Str\replace_first_occurrence() function.
 *
 * @param string $string The string to search in
 * @param string $search The substring to search for
 * @param string $replace The replacement string
 * @return string The string with first occurrence replaced
 */
function replace_first_occurrence(string $string, string $search, string $replace): string
{
    $position = strpos($string, $search);

    if ($position === false) {
        return $string;
    }

    return substr_replace($string, $replace, $position, strlen($search));
}

/**
 * Check if a string starts with a given substring.
 *
 * @param string $haystack The string to search in
 * @param string $needle The substring to search for at the beginning
 * @return bool True if haystack starts with needle, false otherwise
 */
function starts_with(string $haystack, string $needle): bool
{
    return str_starts_with($haystack, $needle);
}

/**
 * Check if a string ends with a given substring.
 *
 * @param string $haystack The string to search in
 * @param string $needle The substring to search for at the end
 * @return bool True if haystack ends with needle, false otherwise
 */
function ends_with(string $haystack, string $needle): bool
{
    return str_ends_with($haystack, $needle);
}

/**
 * Convert a string to lowercase.
 *
 * @param string $string The string to convert
 * @return string The lowercase string
 */
function to_lower_case(string $string): string
{
    return strtolower($string);
}

/**
 * Convert a string to uppercase.
 *
 * @param string $string The string to convert
 * @return string The uppercase string
 */
function to_upper_case(string $string): string
{
    return strtoupper($string);
}