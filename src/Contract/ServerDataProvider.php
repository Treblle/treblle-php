<?php

declare(strict_types=1);

namespace Treblle\Php\Contract;

use Treblle\Php\DataTransferObject\Server;

interface ServerDataProvider
{
    public function getServer(): Server;
}
