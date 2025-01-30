<?php

declare(strict_types=1);

namespace Treblle\Model;

final class Response implements \JsonSerializable
{
    /**
     * Response headers in key:value (json_encoded) format as shown below.
     *
     * @var array<string, string>
     */
    private array $headers;

    /**
     * The HTTP response code.
     * Source: https://www.restapitutorial.com/httpstatuscodes.html.
     */
    private int $code;

    /**
     * The response size in bytes. This represent the total JSON response size in bytes. This can be pulled from
     * Headers but should always prefer language specific methods of getting the response size.
     */
    private float $size;

    /**
     * The load time of the API response in microseconds.
     */
    private float $load_time;

    /**
     * The COMPLETE json response as returned by the server. This should ONLY be a VALID JSON and should be wrapped
     * inside the body object.
     *
     * @var array<int|string, mixed>
     */
    private array $body;

    /**
     * @param array<string, string> $headers
     * @param array<int|string, mixed> $body
     */
    public function __construct(array $headers, int $code, float $size, float $load_time, array $body)
    {
        $this->headers = $headers;
        $this->code = $code;
        $this->size = $size;
        $this->load_time = $load_time;
        $this->body = $body;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getSize(): float
    {
        return $this->size;
    }

    public function getLoadTime(): float
    {
        return $this->load_time;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
