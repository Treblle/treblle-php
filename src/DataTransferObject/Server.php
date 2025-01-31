<?php

declare(strict_types=1);

namespace Treblle\DataTransferObject;

use JsonSerializable;

final class Server implements JsonSerializable
{
    public function __construct(
        private string  $ip = 'bogon',
        private string  $timezone = 'UTC',
        private ?string $software = null,
        private ?string $protocol = null,
        private Os      $os = new Os(),
    ) {
    }

    /**
     * The IP address of the server.
     * If you can not detect this leave empty.
     */
    public function getIp(): ?string
    {
        return $this->ip;
    }

    /**
     * The timezone of the server. Example: UTC, America/New_York, Europe/Berlin...
     * You should set this value to UTC if you can not detect the timezone.
     * Source: https://en.wikipedia.org/wiki/List_of_tz_database_time_zones.
     */
    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    /**
     * What software is used for the web server.
     * If you can not detect this leave empty.
     * Examples: Apache, IIS, nginx...
     */
    public function getSoftware(): ?string
    {
        return $this->software;
    }

    /**
     * We are looking here for HTTP protocol or to be more exact we are trying to see if the HTTP version of the
     * server is HTTP 2. All you need to do is get the HTTP version if it's applicable to your server. If not leave empty.
     */
    public function getProtocol(): ?string
    {
        return $this->protocol;
    }

    /**
     * The OS object contains information about the operating system that is running on the server.
     */
    public function getOs(): ?Os
    {
        return $this->os;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
