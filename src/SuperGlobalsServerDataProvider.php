<?php

declare(strict_types=1);

namespace Treblle;

use Treblle\DataTransferObject\Os;
use Treblle\DataTransferObject\Server;
use Treblle\Contract\ServerDataProvider;

final class SuperGlobalsServerDataProvider implements ServerDataProvider
{
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

    private function getServerVariable(string $variable): ?string
    {
        return $_SERVER[$variable] ?? null;
    }
}
