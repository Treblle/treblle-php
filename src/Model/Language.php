<?php

declare(strict_types=1);

namespace Treblle\Model;

final class Language implements \JsonSerializable
{
    /**
     * The language name should be one of the following: php, net, ruby, js, python.
     */
    private ?string $name;

    /**
     * The language version should be pulled directly from the installed version on the server
     * If you can not get this value leave field empty.
     */
    private ?string $version;
    private ?string $expose_php;
    private ?string $display_errors;

    public function __construct(?string $name, ?string $version, ?string $expose_php, ?string $display_errors)
    {
        $this->name = $name;
        $this->version = $version;
        $this->expose_php = $expose_php;
        $this->display_errors = $display_errors;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getExposePhp(): ?string
    {
        return $this->expose_php;
    }

    public function getDisplayErrors(): ?string
    {
        return $this->display_errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
