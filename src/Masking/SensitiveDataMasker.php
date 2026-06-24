<?php

declare(strict_types=1);

namespace Treblle\Php\Masking;

class SensitiveDataMasker
{
    /** @var array<string, true> Lowercase hash map for O(1) keyword lookup */
    private readonly array $keywordMap;

    /** @param string[] $keywords */
    public function __construct(array $keywords)
    {
        $map = [];
        foreach ($keywords as $kw) {
            $map[strtolower($kw)] = true;
        }
        $this->keywordMap = $map;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function maskData(array $data): array
    {
        return $this->maskArray($data);
    }

    /**
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    public function maskHeaders(array $headers): array
    {
        return $this->maskArray($headers, true);
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<string, mixed>
     */
    private function maskArray(array $data, bool $isHeaders = false): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $stringKey = (string) $key;
            $lowerKey = strtolower($stringKey);

            if (isset($this->keywordMap[$lowerKey])) {
                $result[$stringKey] = $isHeaders
                    ? $this->maskHeaderValue($lowerKey, is_string($value) ? $value : '')
                    : $this->maskValue($value);

                continue;
            }

            if (is_array($value)) {
                $result[$stringKey] = $this->maskArray($value, $isHeaders);
            } else {
                $result[$stringKey] = $this->maybeRedactImage($value);
            }
        }

        return $result;
    }

    private function maskHeaderValue(string $headerName, string $value): string
    {
        if ($headerName === 'authorization') {
            $parts = explode(' ', $value, 2);
            if (count($parts) === 2) {
                return $parts[0] . ' ' . str_repeat('*', mb_strlen($parts[1]));
            }
        }

        return str_repeat('*', mb_strlen($value));
    }

    private function maskValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return str_repeat('*', mb_strlen($value));
        }

        if (is_int($value) || is_float($value)) {
            return str_repeat('*', mb_strlen((string) $value));
        }

        if (is_array($value)) {
            return array_map(fn ($v) => $this->maskValue($v), $value);
        }

        return $value;
    }

    private function maybeRedactImage(mixed $value): mixed
    {
        if (is_string($value) && str_starts_with($value, 'data:image/')) {
            return '[image]';
        }

        return $value;
    }
}
