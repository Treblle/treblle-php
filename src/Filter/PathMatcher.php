<?php

declare(strict_types=1);

namespace Treblle\Php\Filter;

class PathMatcher
{
    /** @var array<string, string> Compiled regex cache keyed by original pattern */
    private array $compiled = [];

    /** @param string[] $excludedPaths */
    public function __construct(private readonly array $excludedPaths)
    {
    }

    public function matches(string $requestPath): bool
    {
        foreach ($this->excludedPaths as $pattern) {
            if ($this->patternMatches($pattern, $requestPath)) {
                return true;
            }
        }

        return false;
    }

    private function patternMatches(string $pattern, string $path): bool
    {
        $regex = $this->toRegex($pattern);

        return (bool) preg_match($regex, $path);
    }

    private function toRegex(string $pattern): string
    {
        if (isset($this->compiled[$pattern])) {
            return $this->compiled[$pattern];
        }

        if (count($this->compiled) >= 100) {
            $this->compiled = [];
        }

        // Detect regex patterns: /pattern/flags
        if (str_starts_with($pattern, '/') && preg_match('/\/[gimsuy]*$/', $pattern)) {
            return $this->compiled[$pattern] = $pattern;
        }

        // Wildcard pattern: contains * or ?
        if (str_contains($pattern, '*') || str_contains($pattern, '?')) {
            return $this->compiled[$pattern] = $this->wildcardToRegex($pattern);
        }

        // Exact match (case-insensitive)
        return $this->compiled[$pattern] = '#^' . preg_quote($pattern, '#') . '$#i';
    }

    private function wildcardToRegex(string $pattern): string
    {
        $parts = preg_split('/(\*|\?)/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return '#^' . preg_quote($pattern, '#') . '$#i';
        }

        $regex = '#^';
        foreach ($parts as $part) {
            if ($part === '*') {
                $regex .= '.*';
            } elseif ($part === '?') {
                $regex .= '.';
            } else {
                $regex .= preg_quote($part, '#');
            }
        }

        return $regex . '$#i';
    }
}
