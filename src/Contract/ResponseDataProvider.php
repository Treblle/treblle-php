<?php

declare(strict_types=1);

namespace Treblle\Contract;

use Treblle\DataTransferObject\Response;

interface ResponseDataProvider
{
    public function getResponse(): Response;
}
