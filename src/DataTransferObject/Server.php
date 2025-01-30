<?php

declare(strict_types=1);

namespace Treblle\DataTransferObject;

use JsonSerializable;

final class Server implements JsonSerializable
{
    public function __construct(
        private ?string $ip,
        private ?string $timezone,
        private ?string $software,
        private ?Os     $os,
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
