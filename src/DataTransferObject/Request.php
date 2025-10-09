<?php

declare(strict_types=1);

namespace Treblle\Php\DataTransferObject;

use JsonSerializable;

/**
 * Represents HTTP request data captured by Treblle.
 *
 * This DTO contains all relevant information about an incoming HTTP request,
 * including headers, body, query parameters, method, URL, and metadata.
 *
 * @package Treblle\Php\DataTransferObject
 */
final readonly class Request implements JsonSerializable
{
    /**
     * Constructs a new Request object.
     *
     * @param string $timestamp The request timestamp in Y-m-d H:i:s format (UTC)
     * @param string $url The complete request URL including query string
     * @param string $ip The client IP address (defaults to 'bogon' for private IPs)
     * @param string $user_agent The User-Agent header value
     * @param string $method The HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param array<string, string> $headers The request headers
     * @param array<int|string, mixed> $query The query string parameters
     * @param array<int|string, mixed> $body The request body data
     * @param string|null $route_path The route pattern (e.g., /api/v1/users/{id})
     */
    public function __construct(
        private string $timestamp,
        private string $url,
        private string $ip = 'bogon',
        private string $user_agent = '',
        private string $method = 'GET',
        private array  $headers = [],
        private array $query = [],
        private array $body = [],
        private ?string $route_path = null,
    ) {
    }

    /**
     * Gets the request timestamp.
     *
     * The timestamp is generated when the request was received and is formatted
     * as Y-m-d H:i:s in UTC timezone.
     *
     * @return string The request timestamp
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    /**
     * Gets the client IP address.
     *
     * Returns the real IPv4 address of the client making the request.
     * Defaults to 'bogon' for private/internal IP addresses.
     *
     * @return string The client IP address
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * Gets the complete request URL.
     *
     * Includes the full URL with protocol, host, path, and query string.
     *
     * @return string The full request URL
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Gets the route pattern path.
     *
     * Used to group similar endpoints together in Treblle. For example,
     * requests to /api/v1/users/123 and /api/v1/users/456 would both
     * use the route path /api/v1/users/{id}.
     *
     * @return string|null The route pattern, or null if not available
     */
    public function getRoutePath(): ?string
    {
        return $this->route_path;
    }

    /**
     * Gets the User-Agent header value.
     *
     * @return string The User-Agent string
     */
    public function getUserAgent(): string
    {
        return $this->user_agent;
    }

    /**
     * Gets the HTTP request method.
     *
     * Common values: GET, POST, PUT, DELETE, PATCH, OPTIONS, HEAD
     * Should be uppercase when possible.
     *
     * @return string The HTTP method (defaults to GET)
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Gets the request headers.
     *
     * Returns headers as a key-value array. Sensitive headers like
     * Authorization may be masked by the field masker.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Gets the request body data.
     *
     * Includes all data sent with the request: form-data, x-www-form-urlencoded,
     * JSON, XML, or raw data. Sensitive fields may be masked by the field masker.
     *
     * @return array<int|string, mixed>
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * Gets the query string parameters.
     *
     * Returns all parameters from the URL query string as an associative array.
     *
     * @return array<int|string, mixed>
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * Serializes the Request object to JSON format.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
