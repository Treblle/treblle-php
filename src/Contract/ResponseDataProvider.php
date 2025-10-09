<?php

declare(strict_types=1);

namespace Treblle\Php\Contract;

use Treblle\Php\DataTransferObject\Response;

interface ResponseDataProvider
{
    public function getResponse(): Response;
}
