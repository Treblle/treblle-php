<?php

declare(strict_types=1);

namespace Treblle\DataTransferObject;

use JsonSerializable;

final readonly class Error implements JsonSerializable
{
    public function __construct(
        private string $message,
        private string $file,
        private int    $line,
        private string $source = 'onError',
        private string $type = 'UNHANDLED_EXCEPTION',
    ) {
    }

    /**
     * The values can be onError, onException, onShutdown
     * This is because in some languages errors can be thrown as exceptions, as regular errors or as shutdown errors.
     * If your language doesn't have this paradigm simply set it to onError.
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Languages often have error types so if you can get.
     * If your language doesn't have this paradigm simply set it to UNHANDLED_EXCEPTION.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * The error message as return to you.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * The name of the file that caused the error.
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * The exact line of code where the error happened.
     * If you can not get this value leave field empty.
     */
    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
