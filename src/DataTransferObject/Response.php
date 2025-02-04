<?php

declare(strict_types=1);

namespace Treblle\DataTransferObject;

use JsonSerializable;

final readonly class Response implements JsonSerializable
{
    public function __construct(
        private int $code = 200, // TODO: default to something that can be identified easily
        private float $size = 0.0,
        private float $load_time = 0.0,
        private array $body = [],
        private array $headers = [],
    ) {
    }

    /**
     * Response headers in key:value (json_encoded) format as shown below.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * The HTTP response code.
     * Source: https://www.restapitutorial.com/httpstatuscodes.html.
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * The response size in bytes. This represents the total JSON response size in bytes. This can be pulled from
     * Headers but should always prefer language specific methods of getting the response size.
     */
    public function getSize(): float
    {
        return $this->size;
    }

    /**
     * The load time of the API response Unix timestamp with microseconds difference from now
     */
    public function getLoadTime(): float
    {
        return $this->load_time;
    }

    /**
     * The COMPLETE json response as returned by the server. This should ONLY be a VALID JSON and should be wrapped
     * inside the body object.
     *
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
