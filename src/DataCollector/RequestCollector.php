<?php

declare(strict_types=1);

namespace Treblle\Php\DataCollector;

class RequestCollector
{
    private const MAX_BODY_BYTES = 2 * 1024 * 1024; // 2MB

    private static ?string $overriddenRoutePath = null;

    /** @var array{timestamp: string, ip: string, url: string, user_agent: string, method: string, headers: array<string, string>, body: array<string, mixed>, route_path: string|null, query: array<string, string>}|null */
    private ?array $cached = null;

    public static function setRoutePath(string $path): void
    {
        self::$overriddenRoutePath = $path;
    }

    public static function getRoutePath(): ?string
    {
        return self::$overriddenRoutePath;
    }

    public static function reset(): void
    {
        self::$overriddenRoutePath = null;
    }

    /**
     * @return array{
     *   timestamp: string,
     *   ip: string,
     *   url: string,
     *   user_agent: string,
     *   method: string,
     *   headers: array<string, string>,
     *   body: array<string, mixed>,
     *   route_path: string|null,
     *   query: array<string, string>
     * }
     */
    public function collect(): array
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $data = [
            'timestamp' => gmdate('Y-m-d H:i:s'),
            'ip' => $this->resolveClientIp(),
            'url' => $this->resolveUrl(),
            'user_agent' => $this->serverString('HTTP_USER_AGENT'),
            'method' => strtoupper($this->serverString('REQUEST_METHOD', 'GET')),
            'headers' => $this->collectHeaders(),
            'body' => $this->parseBody(),
            'route_path' => $this->resolveRoutePath(),
            'query' => $this->collectQuery(),
        ];

        $this->cached = $data;

        return $data;
    }

    private function resolveClientIp(): string
    {
        if (! empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $this->firstIp($this->serverString('HTTP_CLIENT_IP'));
        }

        if (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $this->firstIp($this->serverString('HTTP_X_FORWARDED_FOR'));
        }

        $remoteAddr = $this->serverString('REMOTE_ADDR');
        if ($remoteAddr !== '') {
            return $remoteAddr;
        }

        return 'bogon';
    }

    private function firstIp(string $value): string
    {
        return trim(explode(',', $value)[0]);
    }

    private function resolveUrl(): string
    {
        $https = $this->serverString('HTTPS');
        $scheme = ($https !== '' && $https !== 'off') ? 'https' : 'http';
        $host = $this->serverString('HTTP_HOST')
            ?: $this->serverString('SERVER_NAME', 'localhost');
        $uri = $this->serverString('REQUEST_URI', '/');

        return $scheme . '://' . $host . $uri;
    }

    /** @return array<string, string> */
    private function collectHeaders(): array
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                $headers[$name] = $value;
            }

            return $headers;
        }

        foreach ($_SERVER as $key => $value) {
            if (! is_string($key) || ! is_string($value)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', ucwords(strtolower(substr($key, 5)), '_'));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $name = str_replace('_', '-', ucwords(strtolower($key), '_'));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /** @return array<string, string> */
    private function collectQuery(): array
    {
        $query = [];

        foreach ($_GET as $key => $value) {
            $query[$key] = match (true) {
                is_string($value) => $value,
                is_array($value) => implode(',', array_map(
                    static fn (mixed $v): string => is_string($v) ? $v : '',
                    $value
                )),
                is_int($value), is_float($value) => (string) $value,
                default => '',
            };
        }

        return $query;
    }

    /** @return array<string, mixed> */
    private function parseBody(): array
    {
        $contentType = strtolower($this->serverString('CONTENT_TYPE'));

        if (! empty($_FILES)) {
            return $this->buildFileUploadInfo();
        }

        // Reject oversized bodies before reading — Content-Length is advisory but avoids
        // allocating a large string just to discard it. The capped read below is the hard guard.
        $contentLength = (int) $this->serverString('CONTENT_LENGTH', '0');
        if ($contentLength > self::MAX_BODY_BYTES) {
            return ['error' => 'Payload exceeds 2MB', 'size' => $contentLength];
        }

        // Read at most MAX+1 bytes so we can detect oversized bodies without pulling
        // the entire stream into memory first.
        $rawInput = file_get_contents('php://input', false, null, 0, self::MAX_BODY_BYTES + 1) ?: '';
        $size = mb_strlen($rawInput, '8bit');

        if ($size > self::MAX_BODY_BYTES) {
            return ['error' => 'Payload exceeds 2MB', 'size' => $size];
        }

        if (str_contains($contentType, 'application/json')) {
            if ($rawInput === '') {
                return [];
            }

            $decoded = $this->jsonDecodeArray($rawInput);

            return $decoded ?? ['error' => 'Request payload is not valid JSON'];
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            return $_POST;
        }

        if ($rawInput !== '') {
            $decoded = $this->jsonDecodeArray($rawInput);

            return $decoded ?? ['error' => 'Request payload is not valid JSON'];
        }

        return [];
    }

    /** @return array<string, mixed> */
    private function buildFileUploadInfo(): array
    {
        $uploads = [];

        foreach ($_FILES as $file) {
            if (! is_array($file)) {
                continue;
            }

            $name = $file['name'] ?? null;

            if (is_array($name)) {
                foreach ($name as $i => $fileName) {
                    $uploads[] = [
                        'name' => $fileName,
                        'type' => is_array($file['type'] ?? null) ? ($file['type'][$i] ?? null) : null,
                        'size' => is_array($file['size'] ?? null) ? ($file['size'][$i] ?? null) : null,
                    ];
                }
            } else {
                $uploads[] = [
                    'name' => $name,
                    'type' => $file['type'] ?? null,
                    'size' => $file['size'] ?? null,
                ];
            }
        }

        return ['_treblle_file_upload' => count($uploads) === 1 ? $uploads[0] : $uploads];
    }

    /**
     * Decodes a JSON string to a string-keyed array.
     * JSON object keys are always strings, but PHPStan types json_decode as mixed.
     *
     * @return array<string, mixed>|null
     */
    private function jsonDecodeArray(string $json): ?array
    {
        $data = json_decode($json, true);

        if (! is_array($data)) {
            return null;
        }

        $result = [];
        foreach ($data as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }

    private function resolveRoutePath(): ?string
    {
        if (self::$overriddenRoutePath !== null) {
            return self::$overriddenRoutePath;
        }

        $serverVar = $_SERVER['TREBLLE_ROUTE_PATH'] ?? null;

        return is_string($serverVar) && $serverVar !== '' ? $serverVar : null;
    }

    private function serverString(string $key, string $default = ''): string
    {
        $value = $_SERVER[$key] ?? null;

        return is_string($value) ? $value : $default;
    }
}
