<?php

declare(strict_types=1);

namespace Treblle\Php\DataCollector;

class ErrorCollector
{
    private const int MAX_ERRORS = 25;

    /** @var array<int, array{source: string, type: string, message: string, file: string, line: int}> */
    private array $errors = [];

    public function addError(
        string $source,
        string $type,
        string $message,
        string $file,
        int $line
    ): void {
        if (count($this->errors) >= self::MAX_ERRORS) {
            return;
        }

        $this->errors[] = compact('source', 'type', 'message', 'file', 'line');
    }

    /** @return array<int, array{source: string, type: string, message: string, file: string, line: int}> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
