<?php

declare(strict_types=1);

namespace Treblle\Php\Http;

use Treblle\Php\CircuitBreaker\CircuitBreaker;

class IngressClient
{
    private ?\CurlHandle $handle = null;

    private ?CircuitBreaker $circuitBreaker = null;

    public function send(string $gzippedBody, string $endpoint, string $sdkToken, bool $debug = false): bool
    {
        if ($endpoint === '') {
            return false;
        }

        $this->circuitBreaker ??= CircuitBreaker::create($endpoint);

        if ($this->circuitBreaker->isOpen()) {
            if ($debug) {
                error_log('[TREBLLE] Circuit breaker open, skipping request');
            }

            return false;
        }

        $ch = $this->getHandle();

        $retryAfter = null;

        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $gzippedBody,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Encoding: gzip',
                "x-api-key: {$sdkToken}",
            ],
            CURLOPT_HEADERFUNCTION => static function ($ch, string $header) use (&$retryAfter): int {
                if (stripos($header, 'Retry-After:') === 0) {
                    $value = trim(substr($header, 12));
                    if (is_numeric($value)) {
                        $retryAfter = (int) $value;
                    }
                }

                return strlen($header);
            },
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($debug) {
            if ($curlError !== '') {
                error_log("[TREBLLE] curl error: {$curlError}");
            } elseif ($httpCode >= 500) {
                error_log("[TREBLLE] Received 5xx from Treblle ingress: {$httpCode}");
            } elseif ($httpCode >= 400) {
                error_log("[TREBLLE] Received 4xx from Treblle ingress: {$httpCode}");
            }
        }

        $success = $curlError === '' && $httpCode >= 200 && $httpCode < 300;

        if ($success) {
            $this->circuitBreaker->recordSuccess();
        } else {
            $networkError = $curlError !== '' ? 0 : $httpCode;
            $this->circuitBreaker->recordFailure($networkError, $retryAfter);
        }

        return $success;
    }

    private function getHandle(): \CurlHandle
    {
        if ($this->handle !== null) {
            curl_reset($this->handle);

            return $this->handle;
        }

        $handle = curl_init();
        if ($handle === false) {
            throw new \RuntimeException('[TREBLLE] Failed to initialize curl handle');
        }

        return $this->handle = $handle;
    }

    public function __destruct()
    {
        $this->handle = null;
    }
}
