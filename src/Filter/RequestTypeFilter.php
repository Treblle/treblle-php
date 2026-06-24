<?php

declare(strict_types=1);

namespace Treblle\Php\Filter;

class RequestTypeFilter
{
    private const SKIP_EXTENSIONS = [
        '.env', '.css', '.js', '.html', '.htm', '.ico', '.png', '.jpg',
        '.jpeg', '.gif', '.svg', '.woff', '.woff2', '.ttf', '.eot',
        '.pdf', '.txt', '.xml', '.zip', '.gz', '.tar', '.map',
    ];

    public function shouldSkip(string $url, ?string $responseContentType = null): bool
    {
        $parsed = parse_url($url, PHP_URL_PATH);
        $path = strtolower(is_string($parsed) ? $parsed : $url);

        foreach (self::SKIP_EXTENSIONS as $ext) {
            if (str_ends_with($path, $ext)) {
                return true;
            }
        }

        if ($responseContentType !== null && ! str_contains($responseContentType, 'application/json')) {
            return true;
        }

        return false;
    }
}
