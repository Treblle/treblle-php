<?php

declare(strict_types=1);

namespace Treblle\Model;

final class Data implements \JsonSerializable
{
    private Server $server;
    private Language $language;
    private Request $request;
    private Response $response;

    /**
     * @var list<Error>
     */
    private array $errors;

    /**
     * @param list<Error> $errors
     */
    public function __construct(Server $server, Language $language, Request $request, Response $response, array $errors)
    {
        $this->server = $server;
        $this->language = $language;
        $this->request = $request;
        $this->response = $response;
        $this->errors = $errors;
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
     * @return list<Error>
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
