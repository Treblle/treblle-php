<?php

declare(strict_types=1);

namespace Treblle\Contract;

use Treblle\Model\Request;

interface RequestDataProvider
{
    public function getRequest(): Request;
}
