<?php

declare(strict_types=1);

namespace Treblle\Php\CircuitBreaker\Storage;

class FileStorage implements StorageInterface
{
    private readonly string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? sys_get_temp_dir() . '/treblle_circuit_breaker.json';
    }

    public function increment(string $key): int
    {
        $result = $this->withLock(function (array &$data) use ($key): int {
            $current = isset($data[$key]['val']) && is_int($data[$key]['val'])
                ? $data[$key]['val']
                : 0;
            $data[$key] = ['val' => $current + 1];

            return $current + 1;
        });

        return is_int($result) ? $result : 1;
    }

    public function set(string $key, int $value, int $ttl): void
    {
        $this->withLock(function (array &$data) use ($key, $value, $ttl): int {
            $data[$key] = ['val' => $value, 'exp' => time() + $ttl];

            return 0;
        });
    }

    public function setIfAbsent(string $key, int $value, int $ttl): bool
    {
        $result = $this->withLock(function (array &$data) use ($key, $value, $ttl): bool {
            if (isset($data[$key])) {
                $exp = isset($data[$key]['exp']) && is_int($data[$key]['exp'])
                    ? $data[$key]['exp']
                    : null;
                if ($exp === null || $exp > time()) {
                    return false;
                }
            }

            $data[$key] = ['val' => $value, 'exp' => time() + $ttl];

            return true;
        });

        return is_bool($result) ? $result : false;
    }

    public function get(string $key): ?int
    {
        $result = $this->withLock(function (array &$data) use ($key): ?int {
            if (! isset($data[$key])) {
                return null;
            }

            $exp = isset($data[$key]['exp']) && is_int($data[$key]['exp'])
                ? $data[$key]['exp']
                : null;

            if ($exp !== null && $exp < time()) {
                unset($data[$key]);

                return null;
            }

            $val = $data[$key]['val'] ?? null;

            return is_int($val) ? $val : null;
        });

        return is_int($result) ? $result : null;
    }

    public function delete(string ...$keys): void
    {
        $this->withLock(function (array &$data) use ($keys): int {
            foreach ($keys as $key) {
                unset($data[$key]);
            }

            return 0;
        });
    }

    private function withLock(callable $fn): mixed
    {
        $fp = @fopen($this->path, 'c+');
        if ($fp === false) {
            return null;
        }

        try {
            flock($fp, LOCK_EX);
            $content = stream_get_contents($fp);
            $decoded = ($content !== '' && $content !== false)
                ? json_decode($content, true)
                : null;
            $data = is_array($decoded) ? $decoded : [];

            $result = $fn($data);

            fseek($fp, 0);
            ftruncate($fp, 0);
            fwrite($fp, (string) json_encode($data));

            return $result;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
