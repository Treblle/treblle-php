<?php

declare(strict_types=1);

namespace Treblle\Model;

final class Request implements \JsonSerializable
{
    /**
     * @param array<string, string> $headers
     * @param array<int|string, mixed> $body
     * @param array<int|string, mixed> $raw
     */
    public function __construct(
        private string  $timestamp,
        private string  $ip,
        private string  $url,
        private string  $user_agent,
        private string  $method,
        private array   $headers,
        private array   $body,
        private array   $raw,
        private ?string $route_path = null,
    ) {
    }

    /**
     * The timestamp should be generated at the time the request was made and should be in format of YYYY-MM-DD hh:mm:ss.
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    /**
     * The real IP address of the request.
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
     * A route path of the request excluding query data if it has any.
     */
    public function getRoutePath(): ?string
    {
        return $this->route_path;
    }

    /**
     * The User Agent of the request. This can be pulled from headers but sometimes languages offer their own
     * way of getting the User Agent and if there is one you should use that one.
     */
    public function getUserAgent(): string
    {
        return $this->user_agent;
    }

    /**
     * The HTTP method for the request. Should be uppercased if possible.
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
     * Raw inputs passed to php://input
     *
     * @return array<int|string, mixed>
     */
    public function getRaw(): array
    {
        return $this->raw;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
