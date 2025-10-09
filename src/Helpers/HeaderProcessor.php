<?php

declare(strict_types=1);

namespace Treblle\Php\Helpers;

final class HeaderProcessor
{
    public static function process(array $headers, array $excludedHeaders = []): array
    {
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

    private static function convertPatternToRegex(string $pattern): string
    {
        // Already a regex pattern
        if (preg_match('/^\/.*\/[gimxsu]*$/', $pattern)) {
            return $pattern;
        }

        // Convert shell-style wildcards to regex
        $regex = preg_quote($pattern, '/');
        $regex = str_replace(['\*', '\?'], ['.*', '.'], $regex);

        return '/^' . $regex . '$/i';
    }
}
