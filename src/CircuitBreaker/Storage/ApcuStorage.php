<?php

declare(strict_types=1);

namespace Treblle\Php\CircuitBreaker\Storage;

class ApcuStorage implements StorageInterface
{
    public function increment(string $key): int
    {
        $result = apcu_inc($key, 1, $success);

        if (! $success) {
            // Key absent — initialise to 1. A tiny race window exists here but
            // an off-by-one in the failure counter is acceptable for a circuit breaker.
            apcu_add($key, 1);

            return 1;
        }

        return (int) $result;
    }

    public function set(string $key, int $value, int $ttl): void
    {
        apcu_store($key, $value, $ttl);
    }

    public function setIfAbsent(string $key, int $value, int $ttl): bool
    {
        return (bool) apcu_add($key, $value, $ttl);
    }

    public function get(string $key): ?int
    {
        $value = apcu_fetch($key, $success);

        if (! $success || ! is_int($value)) {
            return null;
        }

        return $value;
    }

    public function delete(string ...$keys): void
    {
        foreach ($keys as $key) {
            apcu_delete($key);
        }
    }
}
