<?php

declare(strict_types=1);

namespace Treblle\Php\Helpers;

/**
 * Filters HTTP headers based on exclusion patterns.
 *
 * This helper class provides functionality to exclude specific headers before sending
 * data to Treblle. Useful for filtering out internal headers, debugging headers,
 * or sensitive authentication tokens that shouldn't be logged.
 *
 * Supports three matching strategies:
 * - Exact matching: "X-Custom-Header" matches only that specific header
 * - Wildcard patterns: "X-Internal-*" matches all headers starting with "X-Internal-"
 * - Regex patterns: "/^Authorization$/i" uses full regex matching
 *
 * @package Treblle\Php\Helpers
 */
final class HeaderFilter
{
    /**
     * @var array<string, string> Cache for compiled regex patterns
     */
    private static array $regexCache = [];

    /**
     * Filters headers by excluding those matching the provided patterns.
     *
     * Processes headers and removes any that match the exclusion patterns.
     * Handles array values by extracting the first element. All pattern
     * matching is case-insensitive by default.
     *
     * Optimized with regex pattern caching to avoid recompilation on repeated calls.
     *
     * Examples:
     * <code>
     * // Exclude specific header
     * HeaderFilter::filter($headers, ['X-Debug-Token']);
     *
     * // Exclude with wildcard
     * HeaderFilter::filter($headers, ['X-Internal-*']);
     *
     * // Exclude with regex
     * HeaderFilter::filter($headers, ['/^X-(Debug|Test)-.* /i']);
     * </code>
     *
     * @param array<string, mixed> $headers The headers to filter (may contain array values)
     * @param list<string> $excludedHeaders The exclusion patterns (exact, wildcard, or regex)
     * @return array<string, string> The filtered headers with excluded ones removed
     */
    public static function filter(array $headers, array $excludedHeaders = []): array
    {
        // Early return if no headers or no exclusions
        if (empty($headers) || empty($excludedHeaders)) {
            // Convert array values to strings
            $processed = [];
            foreach ($headers as $key => $value) {
                $processed[$key] = is_array($value) ? (string) reset($value) : (string) $value;
            }

            return $processed;
        }

        $processed = [];

        foreach ($headers as $key => $value) {
            // Get first value if array
            $headerValue = is_array($value) ? reset($value) : $value;

            // Check if header matches any exclusion pattern
            if (self::isExcluded($key, $excludedHeaders)) {
                continue;
            }

            $processed[$key] = $headerValue;
        }

        return $processed;
    }

    /**
     * Checks if a header key matches any exclusion pattern.
     *
     * Iterates through all exclusion patterns and tests if the header key
     * matches any of them using regex matching.
     *
     * @param string $key The header key to check
     * @param list<string> $excludedHeaders The list of exclusion patterns
     * @return bool True if the header should be excluded, false otherwise
     */
    private static function isExcluded(string $key, array $excludedHeaders): bool
    {
        foreach ($excludedHeaders as $pattern) {
            $regex = self::convertPatternToRegex($pattern);
            if (preg_match($regex, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Converts a pattern to a regular expression with caching.
     *
     * Handles three types of patterns:
     * 1. Regex patterns (already formatted): Returns as-is if wrapped in / /
     * 2. Wildcard patterns: Converts * to .* and ? to . (e.g., "X-*" → "/^X-.*$/i")
     * 3. Exact matches: Wraps in regex anchors with case-insensitive flag
     *
     * All non-regex patterns are converted to case-insensitive regex with
     * anchors to ensure full string matching.
     *
     * Results are cached to avoid repeated compilation of the same patterns.
     *
     * @param string $pattern The pattern to convert (exact, wildcard, or regex)
     * @return string A valid regular expression pattern
     */
    private static function convertPatternToRegex(string $pattern): string
    {
        // Check cache first
        if (isset(self::$regexCache[$pattern])) {
            return self::$regexCache[$pattern];
        }

        // Already a regex pattern
        if (preg_match('/^\/.*\/[gimxsu]*$/', $pattern)) {
            self::$regexCache[$pattern] = $pattern;

            return $pattern;
        }

        // Convert shell-style wildcards to regex
        $regex = preg_quote($pattern, '/');
        $regex = str_replace(['\*', '\?'], ['.*', '.'], $regex);
        $compiled = '/^' . $regex . '$/i';

        // Cache the result
        self::$regexCache[$pattern] = $compiled;

        return $compiled;
    }
}
