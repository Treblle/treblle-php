<?php

declare(strict_types=1);

namespace Treblle\Php\DataTransferObject;

use JsonSerializable;

/**
 * Top-level payload wrapper for Treblle API data transmission.
 *
 * This DTO encapsulates all data sent to the Treblle platform, including
 * server information, language details, HTTP request/response data, and
 * any errors that occurred during request processing.
 *
 * @package Treblle\Php\DataTransferObject
 */
final readonly class Data implements JsonSerializable
{
    /**
     * Constructs a new Data payload.
     *
     * @param Server $server The server information
     * @param Language $language The language/runtime information
     * @param Request $request The HTTP request data
     * @param Response $response The HTTP response data
     * @param list<Error> $errors List of errors that occurred during processing
     */
    public function __construct(
        private Server   $server,
        private Language $language,
        private Request  $request,
        private Response $response,
        private array    $errors
    ) {
    }

    /**
     * Gets the server information.
     *
     * @return Server
     */
    public function getServer(): Server
    {
        return $this->server;
    }

    /**
     * Gets the language/runtime information.
     *
     * @return Language
     */
    public function getLanguage(): Language
    {
        return $this->language;
    }

    /**
     * Gets the HTTP request data.
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Gets the HTTP response data.
     *
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * Gets the list of errors that occurred during request processing.
     *
     * @return list<Error>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Serializes the Data object to JSON format.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
