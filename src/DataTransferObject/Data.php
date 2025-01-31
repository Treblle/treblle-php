<?php

declare(strict_types=1);

namespace Treblle\DataTransferObject;

use JsonSerializable;

final class Data implements JsonSerializable
{
    /**
     * @param list<Error> $errors
     */
    public function __construct(
        private readonly Server   $server,
        private readonly Language $language,
        private readonly Request  $request,
        private readonly Response $response,
        private readonly array $errors
    ) {
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * @return list<Error> $errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
