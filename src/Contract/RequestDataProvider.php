<?php

declare(strict_types=1);

namespace Treblle\Contract;

use Treblle\DataTransferObject\Request;

interface RequestDataProvider
{
    public function getRequest(): Request;
}
