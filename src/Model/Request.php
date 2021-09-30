<?php

declare(strict_types=1);

namespace Treblle\Model;

final class Request implements \JsonSerializable
{
    /**
     * The timestamp should be generated at the time the request was made and should be in format of YYYY-MM-DD hh:mm:ss.
     */
    private string $timestamp;

    /**
     * The real IP address of the request.
     */
    private string $ip;

    /**
     * A full URL of the request including query data if it has any.
     */
    private string $url;

    /**
     * The User Agent of the request. This can be pulled from headers but sometimes languages offer their own
     * way of getting the User Agent and if there is one you should use that one.
     */
    private string $user_agent;

    /**
     * The HTTP method for the request. Should be uppercased if possible.
     */
    private string $method;

    /**
     * Request headers in key:value (json_encoded) format as shown below.
     *
     * @var array<string, string>
     */
    private array $headers;

    /**
     * The COMPLETE request data sent with this request. This should include any form-data values, x-www-urlencoded data,
     * raw data or even query data. Anything that was sent as part of the request data should be shown here wrapped in a
     * body object.
     *
     * @var array<int|string, mixed>
     */
    private array $body;

    /**
     * @todo describe
     *
     * @var array<int|string, mixed>
     */
    private array $raw;

    /**
     * @param array<string, string> $headers
     * @param array<int|string, mixed> $body
     * @param array<int|string, mixed> $raw
     */
    public function __construct(
        string $timestamp,
        string $ip,
        string $url,
        string $user_agent,
        string $method,
        array $headers,
        array $body,
        array $raw
    ) {
        $this->timestamp = $timestamp;
        $this->ip = $ip;
        $this->url = $url;
        $this->user_agent = $user_agent;
        $this->method = $method;
        $this->headers = $headers;
        $this->body = $body;
        $this->raw = $raw;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getUserAgent(): string
    {
        return $this->user_agent;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
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
