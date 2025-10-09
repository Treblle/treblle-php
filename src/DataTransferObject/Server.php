<?php

declare(strict_types=1);

namespace Treblle\Php\DataTransferObject;

use JsonSerializable;

/**
 * Represents server information for API requests.
 *
 * This DTO contains details about the server handling the request,
 * including IP address, timezone, web server software, HTTP protocol,
 * and operating system information.
 *
 * @package Treblle\Php\DataTransferObject
 */
final readonly class Server implements JsonSerializable
{
    /**
     * Constructs a new Server object.
     *
     * @param string $ip The server's IP address (defaults to 'bogon' for private IPs)
     * @param string $timezone The server timezone (defaults to UTC)
     * @param string|null $software The web server software (e.g., Apache, nginx, IIS)
     * @param string|null $protocol The HTTP protocol version (e.g., HTTP/1.1, HTTP/2)
     * @param Os $os The operating system information
     */
    public function __construct(
        private string  $ip = 'bogon',
        private string  $timezone = 'UTC',
        private ?string $software = null,
        private ?string $protocol = null,
        private Os      $os = new Os(),
    ) {
    }

    /**
     * Gets the server IP address.
     *
     * Returns the real IPv4 address of the server. Defaults to 'bogon'
     * for private/internal IP addresses.
     *
     * @return string The server IP address
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * Gets the server timezone.
     *
     * Returns the timezone in which the server is operating.
     * Examples: UTC, America/New_York, Europe/Berlin, Asia/Tokyo
     * Defaults to UTC if the timezone cannot be detected.
     *
     * @see https://en.wikipedia.org/wiki/List_of_tz_database_time_zones
     * @return string The server timezone
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }

    /**
     * Gets the web server software.
     *
     * Examples: Apache, nginx, IIS, LiteSpeed, Caddy
     *
     * @return string|null The web server software, or null if unavailable
     */
    public function getSoftware(): ?string
    {
        return $this->software;
    }

    /**
     * Gets the HTTP protocol version.
     *
     * Used to identify the HTTP version, particularly to detect HTTP/2 usage.
     * Examples: HTTP/1.0, HTTP/1.1, HTTP/2, HTTP/3
     *
     * @return string|null The HTTP protocol version, or null if unavailable
     */
    public function getProtocol(): ?string
    {
        return $this->protocol;
    }

    /**
     * Gets the operating system information.
     *
     * @return Os The OS object containing name, release, and architecture
     */
    public function getOs(): Os
    {
        return $this->os;
    }

    /**
     * Serializes the Server object to JSON format.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
