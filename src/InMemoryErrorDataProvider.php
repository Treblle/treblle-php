<?php

declare(strict_types=1);

namespace Treblle;

use Treblle\Model\Error;
use Treblle\Contract\ErrorDataProvider;

final class InMemoryErrorDataProvider implements ErrorDataProvider
{
    /**
     * @var list<Error>
     */
    private array $errors = [];

    /**
     * @return list<Error>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(Error $error): void
    {
        $this->errors[] = $error;
    }
}
