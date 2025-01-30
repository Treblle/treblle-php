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
            $this->getServerVariable('SERVER_SIGNATURE'),
            $this->getServerVariable('SERVER_PROTOCOL'),
            new Os(
                $this->getName(),
                $this->getRelease(),
                $this->getArchitecture()
            ),
            $this->getServerVariable('HTTP_ACCEPT_ENCODING')
        );
    }

    private function getServerVariable(string $variable): ?string
    {
        return $_SERVER[$variable] ?? null;
    }

    private function getName(): string
    {
        return PHP_OS;
    }

    private function getRelease(): string
    {
        return php_uname('r');
    }

    private function getArchitecture(): string
    {
        return php_uname('m');
    }
}
