<?php

declare(strict_types=1);

namespace Treblle\Php\DataTransferObject;

use JsonSerializable;

final class Os implements JsonSerializable
{
    public function __construct(
        private readonly ?string $name = null,
        private readonly ?string $release = null,
        private readonly ?string $architecture = null,
    ) {
    }

    /**
     * The name of the server OS
     * Example: Linux, Windows...
     * If you can not get this value leave field empty.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * The version of the server OS
     * If you can not get this value leave field empty.
     */
    public function getRelease(): ?string
    {
        return $this->release;
    }

    /**
     * Server architecture
     * If you can not get this value leave field empty.
     */
    public function getArchitecture(): ?string
    {
        return $this->architecture;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
