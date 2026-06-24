<?php

declare(strict_types=1);

namespace Treblle\Php\DataCollector;

class ServerCollector
{
    private static ?string $cachedIp = null;

    /** @var array{name: string|null, release: string|null, architecture: string|null}|null */
    private static ?array $cachedOs = null;

    /** @return array{ip: string, timezone: string, software: string|null, protocol: string|null, os: array{name: string|null, release: string|null, architecture: string|null}} */
    public function collect(): array
    {
        return [
            'ip' => $this->resolveServerIp(),
            'timezone' => date_default_timezone_get() ?: 'UTC',
            'software' => $this->serverStringOrNull('SERVER_SOFTWARE'),
            'protocol' => $this->serverStringOrNull('SERVER_PROTOCOL'),
            'os' => $this->resolveOs(),
        ];
    }

    private function resolveServerIp(): string
    {
        if (self::$cachedIp !== null) {
            return self::$cachedIp;
        }

        $addr = $this->serverStringOrNull('SERVER_ADDR');
        if ($addr !== null) {
            return self::$cachedIp = $addr;
        }

        $hostname = gethostname();
        if ($hostname !== false) {
            $ip = gethostbyname($hostname);
            if ($ip !== $hostname) {
                return self::$cachedIp = $ip;
            }
        }

        return self::$cachedIp = 'bogon';
    }

    /** @return array{name: string|null, release: string|null, architecture: string|null} */
    private function resolveOs(): array
    {
        if (self::$cachedOs !== null) {
            return self::$cachedOs;
        }

        return self::$cachedOs = [
            'name' => php_uname('s') ?: null,
            'release' => php_uname('r') ?: null,
            'architecture' => php_uname('m') ?: null,
        ];
    }

    private function serverStringOrNull(string $key): ?string
    {
        $value = $_SERVER[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
