<?php

declare(strict_types=1);

namespace Treblle\Model;

final class Os implements \JsonSerializable
{
    /**
     * The name of the server OS
     * Example: Linux, Windows...
     * If you can not get this value leave field empty.
     */
    private ?string $name;

    /**
     * The version of the server OS
     * If you can not get this value leave field empty.
     */
    private ?string $release;

    /**
     * Server architecture
     * If you can not get this value leave field empty.
     */
    private ?string $architecture;

    public function __construct(?string $name, ?string $release, ?string $architecture)
    {
        $this->name = $name;
        $this->release = $release;
        $this->architecture = $architecture;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getRelease(): ?string
    {
        return $this->release;
    }

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
