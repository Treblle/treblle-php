<?php

declare(strict_types=1);

namespace Treblle\Php\DataTransferObject;

use JsonSerializable;

/**
 * Represents HTTP response data captured by Treblle.
 *
 * This DTO contains all relevant information about the API response,
 * including status code, headers, body, size, and load time metrics.
 *
 * @package Treblle\Php\DataTransferObject
 */
final readonly class Response implements JsonSerializable
{
    /**
     * Constructs a new Response object.
     *
     * @param int $code The HTTP status code (e.g., 200, 404, 500)
     * @param float $size The response size in bytes
     * @param float $load_time The response load time in seconds
     * @param array<int|string, mixed> $body The response body data
     * @param array<string, string> $headers The response headers
     */
    public function __construct(
        private int $code = 200,
        private float $size = 0.0,
        private float $load_time = 0.0,
        private array $body = [],
        private array $headers = [],
    ) {
    }

    /**
     * Gets the response headers.
     *
     * Returns headers as a key-value array in JSON-compatible format.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Gets the HTTP status code.
     *
     * Common status codes:
     * - 2xx: Success (200 OK, 201 Created, 204 No Content)
     * - 3xx: Redirection (301 Moved Permanently, 302 Found)
     * - 4xx: Client Error (400 Bad Request, 401 Unauthorized, 404 Not Found)
     * - 5xx: Server Error (500 Internal Server Error, 503 Service Unavailable)
     *
     * @return int The HTTP status code (defaults to 200)
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Gets the response size in bytes.
     *
     * Represents the total size of the response payload. Should be calculated
     * using language-specific methods rather than relying solely on headers.
     *
     * @return float The response size in bytes
     */
    public function getSize(): float
    {
        return $this->size;
    }

    /**
     * Gets the response load time.
     *
     * Represents the time taken to generate and return the response,
     * measured in seconds with microsecond precision. Calculated as the
     * difference between request start and response completion.
     *
     * @return float The load time in seconds
     */
    public function getLoadTime(): float
    {
        return $this->load_time;
    }

    /**
     * Gets the response body data.
     *
     * Contains the complete response payload as returned by the server.
     * This should be valid JSON-compatible data. Sensitive fields may
     * be masked by the field masker.
     *
     * @return array<int|string, mixed>
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * Serializes the Response object to JSON format.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
