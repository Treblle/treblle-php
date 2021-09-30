<?php

declare(strict_types=1);

namespace Treblle\Contract;

use Treblle\Model\Response;

interface ResponseDataProvider
{
    public function getResponse(): Response;
}
