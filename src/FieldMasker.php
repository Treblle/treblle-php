<?php

declare(strict_types=1);

namespace Treblle;

use function in_array;
use function is_array;
use function is_string;
use function mb_strlen;

final class FieldMasker
{
    public function __construct(
        public array $fields = [],
    ) {
    }

    public function mask(array $data): array
    {
        $collector = [];
        foreach ($data as $key => $value) {
            $collector[$key] = match (true) {
                is_array($value) => $this->mask(
                    data: $value,
                ),
                is_string($value) => $this->handleString(
                    key: $key,
                    value: $value,
                ),
                default => $value,
            };
        }

        return $collector;
    }

    public function star(string $string): string
    {
        return str_repeat('*', mb_strlen($string));
    }

    private function handleString(bool|float|int|string $key, string $value): string
    {
        if (! is_string($key)) {
            $key = (string) $key;
        }

        static $lowerFields = null;
        if (null === $lowerFields) {
            $lowerFields = array_map('strtolower', $this->fields);
        }

        $lowerKey = mb_strtolower($key);

        if (in_array($lowerKey, $lowerFields, true)) {
            return $this->star($value);
        }

        if ($this->isSensitiveHeader($lowerKey)) {
            return $this->maskAuthorization($value);
        }

        if ($this->isBase64($value)) {
            return 'base64 encoded images are too big to process';
        }

        return $value;
    }

    private function maskAuthorization(string $value): string
    {
        $parts = explode(' ', $value, 2);
        if (isset($parts[1])) {
            $authTypeLower = mb_strtolower($parts[0]);
            if (in_array($authTypeLower, ['bearer', 'basic', 'digest'], true)) {
                return $parts[0] . ' ' . $this->star($parts[1]);
            }
        }

        return $this->star($value);
    }

    private function isSensitiveHeader(string $key): bool
    {
        return in_array($key, ['authorization', 'x-api-key'], true);
    }

    private function isBase64(string $string): bool
    {
        return str_starts_with($string, 'data:image/') && str_contains($string, ';base64,');
    }
}
