<?php

declare(strict_types=1);

namespace Treblle\Php\DataCollector;

class ResponseCollector
{
    private const int MAX_BODY_BYTES = 2 * 1024 * 1024; // 2MB

    /**
     * @return array{
     *   headers: array<string, string>,
     *   code: int,
     *   size: int,
     *   load_time: float,
     *   body: array<string, mixed>,
     *   content_type: string|null
     * }
     */
    public function collect(): array
    {
        $rawBody = ob_get_contents() ?: '';
        $size = mb_strlen($rawBody, '8bit');
        $headers = $this->collectHeaders();
        $contentType = $this->extractContentType($headers);

        $body = $this->parseBody($rawBody, $size);
        $loadTime = $this->calculateLoadTime();

        return [
            'headers' => $headers,
            'code' => $this->resolveStatusCode(),
            'size' => $size,
            'load_time' => $loadTime,
            'body' => $body,
            'content_type' => $contentType,
        ];
    }

    private function resolveStatusCode(): int
    {
        $code = http_response_code();

        return is_int($code) && $code > 0 ? $code : 200;
    }

    /** @return array<string, string> */
    private function collectHeaders(): array
    {
        $headers = [];

        foreach (headers_list() as $header) {
            $pos = strpos($header, ':');
            if ($pos === false) {
                continue;
            }

            $name = trim(substr($header, 0, $pos));
            $value = trim(substr($header, $pos + 1));
            $headers[$name] = $value;
        }

        return $headers;
    }

    /** @param array<string, string> $headers */
    private function extractContentType(array $headers): ?string
    {
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'content-type') {
                return $value;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function parseBody(string $rawBody, int $size): array
    {
        if ($rawBody === '') {
            return [];
        }

        if ($size > self::MAX_BODY_BYTES) {
            return ['error' => 'Payload exceeds 2MB', 'size' => $size];
        }

        $data = json_decode($rawBody, true);

        if (! is_array($data)) {
            return ['error' => 'Response payload is not valid JSON'];
        }

        $result = [];
        foreach ($data as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }

    private function calculateLoadTime(): float
    {
        $start = $_SERVER['REQUEST_TIME_FLOAT'] ?? null;

        if (! is_float($start) && ! is_int($start)) {
            return 0.0;
        }

        return round((microtime(true) - $start) * 1000, 2);
    }
}
