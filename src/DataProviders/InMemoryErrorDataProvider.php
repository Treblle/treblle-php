<?php

declare(strict_types=1);

namespace Treblle\Php\DataProviders;

use Treblle\Php\DataTransferObject\Error;
use Treblle\Php\Contract\ErrorDataProvider;

/**
 * Provides error storage using an in-memory array.
 *
 * This data provider stores PHP errors, exceptions, and shutdown errors
 * in memory during the request lifecycle. Errors are collected by the
 * Treblle error handlers (onError, onException, onShutdown) and sent
 * to Treblle at the end of the request.
 *
 * This is the default error provider used by TreblleFactory.
 *
 * @package Treblle\Php\DataProviders
 */
final class InMemoryErrorDataProvider implements ErrorDataProvider
{
    /**
     * @var list<Error> In-memory storage for errors
     */
    private array $errors = [];

    /**
     * Gets all errors collected during the request.
     *
     * Returns all Error objects that have been added via addError()
     * during the current request lifecycle.
     *
     * @return list<Error> Array of Error objects
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Adds an error to the in-memory collection.
     *
     * Called by Treblle's error handlers when errors, exceptions,
     * or shutdown errors occur. Errors are stored in the order they
     * are encountered.
     *
     * @param Error $error The error object to store
     * @return void
     */
    public function addError(Error $error): void
    {
        $this->errors[] = $error;
    }
}
