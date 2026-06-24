<?php

declare(strict_types=1);

namespace Treblle\Php\CircuitBreaker;

use Treblle\Php\CircuitBreaker\Storage\ApcuStorage;
use Treblle\Php\CircuitBreaker\Storage\FileStorage;
use Treblle\Php\CircuitBreaker\Storage\StorageInterface;

class CircuitBreaker
{
    // How many consecutive 5xx/network failures before opening the circuit.
    private const FAILURE_THRESHOLD = 5;

    // Backoff in seconds for the first open after crossing the threshold.
    // Doubles per failure above threshold, capped at MAX_BACKOFF.
    private const BASE_BACKOFF = 30;
    private const MAX_BACKOFF = 1800;

    // Fallback when the server sends 429 without a Retry-After header.
    private const DEFAULT_RETRY_AFTER = 60;

    // How long the half-open probe slot is held. If the probe worker crashes
    // before recording a result the circuit re-allows traffic after this TTL.
    private const PROBE_TTL = 10;

    // TTL slack added to 'until' key to keep it alive through the probe window.
    private const UNTIL_TTL_SLACK = 60;

    // Adds ±JITTER_FACTOR randomisation to backoff to spread recovery probes.
    private const JITTER_FACTOR = 0.2;

    private readonly string $prefix;

    public function __construct(
        private readonly StorageInterface $storage,
        string $endpoint,
    ) {
        $this->prefix = 'treblle_cb_' . substr(md5($endpoint), 0, 8) . '_';
    }

    public static function create(string $endpoint): self
    {
        $storage = function_exists('apcu_enabled') && apcu_enabled()
            ? new ApcuStorage()
            : new FileStorage();

        return new self($storage, $endpoint);
    }

    /**
     * Returns true when the circuit is open and the caller should skip the request.
     * Returns false (allow through) when:
     *   - The circuit is closed (no failures recorded).
     *   - The backoff period has expired AND this caller won the half-open probe
     *     slot. All other concurrent callers are blocked until the probe records
     *     its result, preventing a thundering herd on recovery.
     *
     * The 'until' key stores an absolute timestamp as its value (not a TTL flag)
     * so the transition from OPEN → HALF-OPEN is controlled explicitly here
     * rather than by storage expiry — allowing exactly one probe worker through.
     */
    public function isOpen(): bool
    {
        $until = $this->storage->get($this->prefix . 'until');

        if ($until === null) {
            return false;
        }

        if (time() < $until) {
            return true;
        }

        // Backoff expired — exactly one worker gets the probe slot.
        return ! $this->storage->setIfAbsent($this->prefix . 'probe', 1, self::PROBE_TTL);
    }

    /**
     * A successful response closes the circuit and resets all state.
     */
    public function recordSuccess(): void
    {
        $this->storage->delete(
            $this->prefix . 'count',
            $this->prefix . 'until',
            $this->prefix . 'probe',
        );
    }

    /**
     * Records a failed response and opens the circuit when appropriate.
     *
     * - 429: opens immediately for Retry-After seconds (or DEFAULT_RETRY_AFTER).
     * - 5xx / 0 (network error): increments the failure counter; opens once
     *   FAILURE_THRESHOLD is reached, with exponential backoff.
     * - 4xx (non-429): config-level errors that waiting cannot fix — not circuit-broken.
     */
    public function recordFailure(int $httpCode, ?int $retryAfterSeconds = null): void
    {
        if ($httpCode === 429) {
            $seconds = $this->jitter($retryAfterSeconds ?? self::DEFAULT_RETRY_AFTER);
            $this->storage->set(
                $this->prefix . 'until',
                time() + $seconds,
                $seconds + self::UNTIL_TTL_SLACK,
            );

            return;
        }

        if ($httpCode >= 500 || $httpCode === 0) {
            $count = $this->storage->increment($this->prefix . 'count');

            if ($count >= self::FAILURE_THRESHOLD) {
                $exponent = $count - self::FAILURE_THRESHOLD;
                $base = (int) min(self::BASE_BACKOFF * (2 ** $exponent), self::MAX_BACKOFF);
                $backoff = $this->jitter($base);
                $this->storage->set(
                    $this->prefix . 'until',
                    time() + $backoff,
                    $backoff + self::UNTIL_TTL_SLACK,
                );
                // Clear any existing probe slot so the new backoff gets a fresh probe.
                $this->storage->delete($this->prefix . 'probe');
            }
        }
        // 4xx (non-429): no action.
    }

    private function jitter(int $seconds): int
    {
        $range = (int) round($seconds * self::JITTER_FACTOR);
        if ($range === 0) {
            return $seconds;
        }

        return $seconds + random_int(-$range, $range);
    }
}
