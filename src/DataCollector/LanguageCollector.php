<?php

declare(strict_types=1);

namespace Treblle\Php\DataCollector;

class LanguageCollector
{
    /** @return array{name: string, version: string} */
    public function collect(): array
    {
        return [
            'name' => 'php',
            'version' => PHP_VERSION,
        ];
    }
}
