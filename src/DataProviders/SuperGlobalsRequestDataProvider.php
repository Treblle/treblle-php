<?php

declare(strict_types=1);

namespace Treblle\Php\DataProviders;

use Treblle\Php\Helpers\HeaderFilter;
use Treblle\Php\DataTransferObject\Request;
use Treblle\Php\Helpers\SensitiveDataMasker;
use Treblle\Php\Contract\RequestDataProvider;

/**
 * Provides HTTP request data using PHP's superglobals.
 *
 * This data provider collects request information from $_SERVER, $_REQUEST,
 * and getallheaders(). It handles sensitive data masking and header filtering
 * before sending data to Treblle.
 *
 * Features:
 * - Masks sensitive fields in request body
 * - Filters headers based on exclusion patterns
 * - Detects client IP through proxy headers (X-Forwarded-For)
 * - Constructs full request URL with protocol detection
 *
 * @package Treblle\Php\DataProviders
 */
final readonly class SuperGlobalsRequestDataProvider implements RequestDataProvider
{
    /**
     * Constructs a new SuperGlobalsRequestDataProvider.
     *
     * @param SensitiveDataMasker $masker The data masker for sensitive fields
     * @param list<string> $excludedHeaders Header patterns to exclude from Treblle
     */
    public function __construct(
        private SensitiveDataMasker $masker,
        private array $excludedHeaders = []
    ) {
    }

    /**
     * Gets HTTP request data from superglobals.
     *
     * Collects and processes:
     * - Timestamp in UTC (Y-m-d H:i:s format)
     * - Full request URL with protocol and query string
     * - Client IP address (with proxy detection)
     * - User-Agent header
     * - HTTP method (GET, POST, etc.)
     * - All request headers (filtered)
     * - Request body data (masked for sensitive fields)
     *
     * @return Request The request data transfer object
     */
    public function getRequest(): Request
    {
        // Avoid function call if no headers to filter
        $headers = getallheaders();
        $filteredHeaders = empty($this->excludedHeaders)
            ? $headers
            : HeaderFilter::filter($headers, $this->excludedHeaders);

        return new Request(
            timestamp: gmdate('Y-m-d H:i:s'),
            url: $this->getEndpointUrl(),
            ip: $this->getClientIpAddress(),
            user_agent: $_SERVER['HTTP_USER_AGENT'] ?? '',
            method: $_SERVER['REQUEST_METHOD'] ?? 'GET',
            headers: $filteredHeaders,
            body: $this->masker->mask($_REQUEST),
        );
    }

    /**
     * Gets the client IP address with proxy detection.
     *
     * Attempts to detect the real client IP address by checking:
     * 1. HTTP_CLIENT_IP header (if set)
     * 2. HTTP_X_FORWARDED_FOR header (for proxied requests)
     * 3. REMOTE_ADDR (direct connection)
     *
     * Defaults to 'bogon' if no IP address can be determined.
     *
     * @return string The client IP address or 'bogon'
     */
    private function getClientIpAddress(): string
    {
        // Check in priority order with null coalescing for performance
        return $_SERVER['HTTP_CLIENT_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? 'bogon';
    }

    /**
     * Constructs the complete request URL.
     *
     * Builds the full URL including:
     * - Protocol (http:// or https:// based on HTTPS server variable)
     * - Host from HTTP_HOST header
     * - Request URI including path and query string
     *
     * Example: https://api.example.com/users?page=1
     *
     * @return string The complete request URL
     */
    private function getEndpointUrl(): string
    {
        // Optimized HTTPS detection
        $isHttps = ! empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS'];
        $protocol = $isHttps ? 'https://' : 'http://';

        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
}
