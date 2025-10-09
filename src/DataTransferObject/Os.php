<?php

declare(strict_types=1);

namespace Treblle\Php\DataTransferObject;

use JsonSerializable;

/**
 * Represents operating system information for the server.
 *
 * This DTO contains details about the server's operating system,
 * including name, release version, and architecture.
 *
 * @package Treblle\Php\DataTransferObject
 */
final class Os implements JsonSerializable
{
    /**
     * Constructs a new Os object.
     *
     * @param string|null $name The operating system name (e.g., Linux, Windows, macOS)
     * @param string|null $release The OS version/release number
     * @param string|null $architecture The system architecture (e.g., x86_64, arm64)
     */
    public function __construct(
        private readonly ?string $name = null,
        private readonly ?string $release = null,
        private readonly ?string $architecture = null,
    ) {
    }

    /**
     * Gets the operating system name.
     *
     * Examples: Linux, Windows, Darwin (macOS), FreeBSD
     *
     * @return string|null The OS name, or null if unavailable
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Gets the operating system version/release.
     *
     * Examples: 5.4.0-42-generic, 10.0.19041, 21.6.0
     *
     * @return string|null The OS version, or null if unavailable
     */
    public function getRelease(): ?string
    {
        return $this->release;
    }

    /**
     * Gets the system architecture.
     *
     * Examples: x86_64, i386, arm64, aarch64
     *
     * @return string|null The system architecture, or null if unavailable
     */
    public function getArchitecture(): ?string
    {
        return $this->architecture;
    }

    /**
     * Serializes the Os object to JSON format.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
