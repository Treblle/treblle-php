<?php

declare(strict_types=1);

namespace Treblle\Php\DataTransferObject;

use JsonSerializable;

/**
 * Represents programming language and runtime information.
 *
 * This DTO contains details about the language and runtime environment
 * executing the API request, including the language name and version.
 *
 * @package Treblle\Php\DataTransferObject
 */
final readonly class Language implements JsonSerializable
{
    /**
     * Constructs a new Language object.
     *
     * @param string $name The language name (e.g., php, python, ruby, js, net)
     * @param string|null $version The language version (defaults to PHP_VERSION)
     */
    public function __construct(
        private string  $name = 'php',
        private ?string $version = PHP_VERSION
    ) {
    }

    /**
     * Gets the language name.
     *
     * Supported values: php, net, ruby, js, python
     * For PHP SDK, this always returns 'php'.
     *
     * @return string The language name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the language version.
     *
     * Returns the version of the language runtime installed on the server.
     * For PHP, this is typically pulled from PHP_VERSION constant.
     * Example: 8.2.15, 8.3.0, 7.4.33
     *
     * @return string|null The language version, or null if unavailable
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * Serializes the Language object to JSON format.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
