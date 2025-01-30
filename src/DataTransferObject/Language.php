<?php

declare(strict_types=1);

namespace Treblle\DataTransferObject;

use JsonSerializable;

final class Language implements JsonSerializable
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

    public function __construct(?string $name, ?string $version)
    {
        $this->name = $name;
        $this->version = $version;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
