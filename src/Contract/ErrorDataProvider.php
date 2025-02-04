<?php

declare(strict_types=1);

namespace Treblle\Contract;

use Treblle\DataTransferObject\Error;

interface ErrorDataProvider
{
    /**
     * @return list<Error>
     */
    public function getErrors(): array;

    public function addError(Error $error): void;
}
