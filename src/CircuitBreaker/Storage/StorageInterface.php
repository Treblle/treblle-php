<?php

declare(strict_types=1);

namespace Treblle\Php\CircuitBreaker\Storage;

interface StorageInterface
{
    /**
     * Atomically increment key by 1, initialising to 1 if absent.
     * The counter has no TTL — it persists until explicitly deleted.
     */
    public function increment(string $key): int;

    /**
     * Overwrite key with value unconditionally.
     * Used for the open-until timestamp — always written on failure.
     */
    public function set(string $key, int $value, int $ttl): void;

    /**
     * Set key to value only if the key does not already exist (or has expired).
     * Returns true if the value was written, false if the key was already present.
     * Used exclusively for the half-open probe slot.
     */
    public function setIfAbsent(string $key, int $value, int $ttl): bool;

    /**
     * Return the stored integer value, or null if the key is absent or expired.
     */
    public function get(string $key): ?int;

    /**
     * Delete one or more keys.
     */
    public function delete(string ...$keys): void;
}
