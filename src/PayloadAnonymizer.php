<?php

declare(strict_types=1);

namespace Treblle;

use Assert\Assertion;

final class PayloadAnonymizer
{
    /**
     * @var list<string>
     */
    private array $masked;

    /**
     * @param list<string> $masked
     */
    public function __construct(array $masked)
    {
        Assertion::allString($masked);
        $this->masked = $masked;
    }

    /**
     * @param array<int|string, mixed> $data
     *
     * @return array<int|string, mixed>
     */
    public function annonymize(array $data): array
    {
        foreach ($data as $key => &$value) {
            if (!\is_string($key)) {
                // @todo check if this should be handled otherwise
                continue;
            }

            if (\is_array($value)) {
                $value = $this->annonymize($value);

                continue;
            }

            if (!\is_string($value)) {
                // @todo check if this should be handled otherwise
                // Cannot mask non-string value
                continue;
            }

            foreach ($this->masked as $field) {
                $regexp = \Safe\sprintf('/\b%s\b/mi', preg_quote($field, '\\'));
                if (\Safe\preg_match($regexp, $key) === 1) {
                    $value = str_repeat('*', mb_strlen($value));
                }
            }
        }

        return $data;
    }
}
