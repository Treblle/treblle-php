<?php

declare(strict_types=1);

namespace Treblle\Php\DataProviders;

use Treblle\Php\DataTransferObject\Os;
use Treblle\Php\DataTransferObject\Server;
use Treblle\Php\Contract\ServerDataProvider;

/**
 * Provides server information using PHP's superglobals and built-in functions.
 *
 * This data provider collects server details from $_SERVER and php_uname(),
 * including IP address, timezone, web server software, HTTP protocol,
 * and operating system information.
 *
 * @package Treblle\Php\DataProviders
 */
final class SuperGlobalsServerDataProvider implements ServerDataProvider
{
    /**
     * Gets server information from superglobals and system functions.
     *
     * Collects:
     * - Server IP address (defaults to 'bogon' if unavailable)
     * - Timezone from PHP configuration
     * - Web server software (e.g., Apache, nginx)
     * - HTTP protocol version (e.g., HTTP/1.1, HTTP/2)
     * - OS details: name, release, and architecture
     *
     * @return Server The server data transfer object
     */
    public function getServer(): Server
    {
        return new Server(
            ip: $this->getServerVariable('SERVER_ADDR') ?? 'bogon',
            timezone: date_default_timezone_get(),
            software: $this->getServerVariable('SERVER_SOFTWARE'),
            protocol: $this->getServerVariable('SERVER_PROTOCOL'),
            os: new Os(
                php_uname('s'),
                php_uname('r'),
                php_uname('m'),
            ),
        );
    }

    /**
     * Safely retrieves a server variable from the $_SERVER superglobal.
     *
     * @param string $variable The server variable name (e.g., 'SERVER_ADDR')
     * @return string|null The variable value, or null if not set
     */
    private function getServerVariable(string $variable): ?string
    {
        return $_SERVER[$variable] ?? null;
    }
}
