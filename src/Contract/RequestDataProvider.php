<?php

declare(strict_types=1);

namespace Treblle\Php\Contract;

use Treblle\Php\DataTransferObject\Request;

interface RequestDataProvider
{
    public function getRequest(): Request;
}
