<?php

declare(strict_types=1);

namespace Treblle;

use Treblle\Contract\ErrorDataProvider;
use Treblle\Model\Error;

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
