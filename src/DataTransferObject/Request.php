<?php

declare(strict_types=1);

namespace Treblle\Php\DataTransferObject;

use JsonSerializable;

final readonly class Request implements JsonSerializable
{
    /**
     * @param array<string, string> $headers
     * @param array<int|string, mixed> $body
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
     * The timestamp should be generated at the time the request was made and should be in format of Y-m-d H:i:s based on UTC timezone.
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    /**
     * The real IPV4 IP address of the request.
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * A full URL of the request including query data if it has any.
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * This will be used to create endpoint so send if you have it correctly only.
     * Example: api/v1/workspaces/{workspaceId}
     */
    public function getRoutePath(): ?string
    {
        return $this->route_path;
    }

    /**
     * The User Agent of the request.
     */
    public function getUserAgent(): string
    {
        return $this->user_agent;
    }

    /**
     * The HTTP method for the request.
     * Should be uppercased if possible.
     * default is GET
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Request headers in key:value (json_encoded) format as shown below.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * The COMPLETE request data sent with this request. This should include any form-data values, x-www-urlencoded data,
     * raw data or even query data. Anything that was sent as part of the request data should be shown here wrapped in a
     * body object.
     *
     * @return array<int|string, mixed>
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * The COMPLETE request query data sent with this request.
     *
     * @return array<int|string, mixed>
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
