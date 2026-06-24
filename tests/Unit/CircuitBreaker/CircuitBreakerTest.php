<?php

declare(strict_types=1);

namespace Treblle\Php\Tests\Unit\CircuitBreaker;

use PHPUnit\Framework\TestCase;
use Treblle\Php\CircuitBreaker\CircuitBreaker;
use Treblle\Php\CircuitBreaker\Storage\StorageInterface;

class CircuitBreakerTest extends TestCase
{
    private InMemoryStorage $storage;

    private CircuitBreaker $cb;

    protected function setUp(): void
    {
        $this->storage = new InMemoryStorage();
        $this->cb = new CircuitBreaker($this->storage, 'https://ingress.treblle.com');
    }

    public function testClosedByDefault(): void
    {
        self::assertFalse($this->cb->isOpen());
    }

    public function testOpensAfterFailureThreshold(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->cb->recordFailure(500);
        }

        self::assertTrue($this->cb->isOpen());
    }

    public function testDoesNotOpenBelowThreshold(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->cb->recordFailure(500);
        }

        self::assertFalse($this->cb->isOpen());
    }

    public function testOpensImmediatelyOn429(): void
    {
        $this->cb->recordFailure(429, 120);

        self::assertTrue($this->cb->isOpen());
    }

    public function testOpensImmediatelyOn429WithoutRetryAfter(): void
    {
        $this->cb->recordFailure(429);

        self::assertTrue($this->cb->isOpen());
    }

    public function test4xxNonRateLimitDoesNotOpen(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->cb->recordFailure(403);
        }

        self::assertFalse($this->cb->isOpen());
    }

    public function testNetworkErrorCountsAsFailure(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->cb->recordFailure(0);
        }

        self::assertTrue($this->cb->isOpen());
    }

    public function testSuccessClosesCircuit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->cb->recordFailure(500);
        }

        self::assertTrue($this->cb->isOpen());

        $this->cb->recordSuccess();

        self::assertFalse($this->cb->isOpen());
    }

    public function testCircuitAllowsProbeAfterBackoffExpires(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->cb->recordFailure(500);
        }

        self::assertTrue($this->cb->isOpen());

        // Simulate the stored backoff timestamp being in the past.
        $this->storage->expireUntilTimestamp();

        // Circuit should allow one probe through.
        self::assertFalse($this->cb->isOpen());
    }

    public function testOnlyOneWorkerGetsHalfOpenProbeSlot(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->cb->recordFailure(500);
        }

        $this->storage->expireUntilTimestamp();

        // First caller wins the probe slot → allowed through (isOpen = false).
        self::assertFalse($this->cb->isOpen());

        // Second caller is blocked while probe is in-flight (isOpen = true).
        self::assertTrue($this->cb->isOpen());
    }
}

/**
 * In-memory storage for testing — avoids APCu / file I/O.
 *
 * @internal
 */
class InMemoryStorage implements StorageInterface
{
    /** @var array<string, array{val: int, exp: int|null}> */
    private array $store = [];

    public function increment(string $key): int
    {
        $current = $this->store[$key]['val'] ?? 0;
        $this->store[$key] = ['val' => $current + 1, 'exp' => null];

        return $current + 1;
    }

    public function set(string $key, int $value, int $ttl): void
    {
        $this->store[$key] = ['val' => $value, 'exp' => time() + $ttl];
    }

    public function setIfAbsent(string $key, int $value, int $ttl): bool
    {
        if (isset($this->store[$key])) {
            $entry = $this->store[$key];
            if ($entry['exp'] === null || $entry['exp'] > time()) {
                return false;
            }
        }

        $this->store[$key] = ['val' => $value, 'exp' => time() + $ttl];

        return true;
    }

    public function get(string $key): ?int
    {
        if (! isset($this->store[$key])) {
            return null;
        }

        $entry = $this->store[$key];

        if ($entry['exp'] !== null && $entry['exp'] <= time()) {
            unset($this->store[$key]);

            return null;
        }

        return $entry['val'];
    }

    public function delete(string ...$keys): void
    {
        foreach ($keys as $key) {
            unset($this->store[$key]);
        }
    }

    /**
     * Simulate the backoff period having elapsed: set all 'until' key values
     * to a past timestamp so isOpen() reaches the half-open probe logic.
     * The key itself remains present (long TTL) — only its VALUE becomes past.
     */
    public function expireUntilTimestamp(): void
    {
        foreach (array_keys($this->store) as $key) {
            if (str_contains($key, '_until')) {
                $this->store[$key]['val'] = time() - 1;
            }
        }
    }
}
