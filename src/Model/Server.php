<?php

declare(strict_types=1);

namespace Treblle\Model;

use JsonSerializable;

final class Server implements JsonSerializable
{
    /**
     * The IP address of the server.
     */
    private ?string $ip;

    /**
     * The timezone of the server. Example: UTC, America/New_York, Europe/Berlin...
     * You should set this value to UTC if you can not detect the timezone.
     * Source: https://en.wikipedia.org/wiki/List_of_tz_database_time_zones.
     */
    private ?string $timezone;

    /**
     * What software is used for the web server.
     * If you can not detect this leavel empty.
     * Examples: Apache, IIS, nginx...
     */
    private ?string $software;

    /**
     * By default Apache has a server signature which is a big security risk. Here we are simply getting the
     * server signature if there is one. Maybe not all web servers have this. You can leave empty
     * if yor server does not have this
     * Source: https://httpd.apache.org/docs/2.4/mod/core.html#serversignature.
     */
    private ?string $signature;

    /**
     * We are looking here for HTTP protocol or to be more exact we are trying to see if the HTTP version of the
     * server is HTTP 2. All you need to do is get the HTTP version if it's applicable to your server. If not leave empty.
     */
    private ?string $protocol;

    /**
     * The OS object contains information about the operating system that is running on the server.
     */
    private ?Os $os;

    public function __construct(
        ?string $ip,
        ?string $timezone,
        ?string $software,
        ?string $signature,
        ?string $protocol,
        ?Os $os,
    ) {
        $this->ip = $ip;
        $this->timezone = $timezone;
        $this->software = $software;
        $this->signature = $signature;
        $this->protocol = $protocol;
        $this->os = $os;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function getOs(): ?Os
    {
        return $this->os;
    }

    public function getSoftware(): ?string
    {
        return $this->software;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function getProtocol(): ?string
    {
        return $this->protocol;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
