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
            $this->getServerVariable('SERVER_ADDR'),
            date_default_timezone_get(),
            $this->getServerVariable('SERVER_SOFTWARE'),
            new Os(
                PHP_OS,
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
