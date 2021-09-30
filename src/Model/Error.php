<?php

declare(strict_types=1);

namespace Treblle\Model;

final class Error implements \JsonSerializable
{
    /**
     * The values can be onError, onException, onShutdown
     * This is because in some languages erors can be thrown as exceptions, as regular errors or as shudown errors.
     * If your language doesn't have this paradigm simply set it to onError.
     */
    private ?string $source;

    /**
     * Languages often have error types so if you can get.
     */
    private ?string $type;

    /**
     * The error message as return to you.
     */
    private ?string $message;

    /**
     * The name of the file that caused the error.
     * If you can not get this value leave field empty.
     */
    private ?string $file;

    /**
     * The exact line of code where the error happend.
     * If you can not get this value leave field empty.
     */
    private ?int $line;

    public function __construct(?string $source, ?string $type, ?string $message, ?string $file, ?int $line)
    {
        $this->source = $source;
        $this->type = $type;
        $this->message = $message;
        $this->file = $file;
        $this->line = $line;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function getLine(): ?int
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
