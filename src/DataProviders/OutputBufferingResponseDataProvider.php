<?php

declare(strict_types=1);

namespace Treblle\Php\DataProviders;

use Exception;
use RuntimeException;
use Treblle\Php\Helpers\HeaderFilter;
use Treblle\Php\DataTransferObject\Error;
use Treblle\Php\Contract\ErrorDataProvider;
use Treblle\Php\DataTransferObject\Response;
use Treblle\Php\Helpers\SensitiveDataMasker;
use Treblle\Php\Contract\ResponseDataProvider;

/**
 * Provides HTTP response data using PHP's output buffering.
 *
 * This data provider captures response information using ob_* functions and
 * headers_list(). It requires output buffering to be enabled (ob_start must
 * be called before instantiation).
 *
 * Features:
 * - Captures response body from output buffer
 * - Masks sensitive fields in response data
 * - Filters headers based on exclusion patterns
 * - Calculates response size and load time
 * - Handles large responses (>2MB) and invalid JSON
 *
 * @package Treblle\Php\DataProviders
 */
final readonly class OutputBufferingResponseDataProvider implements ResponseDataProvider
{
    /**
     * Constructs a new OutputBufferingResponseDataProvider.
     *
     * @param SensitiveDataMasker $fieldMasker The data masker for sensitive fields
     * @param ErrorDataProvider $errorDataProvider Error provider for logging issues
     * @param list<string> $excludedHeaders Header patterns to exclude from Treblle
     * @throws RuntimeException If output buffering is not enabled
     */
    public function __construct(
        private SensitiveDataMasker $fieldMasker,
        private ErrorDataProvider   $errorDataProvider,
        private array               $excludedHeaders = []
    ) {
        if (ob_get_level() < 1) {
            throw new RuntimeException('Output buffering must be enabled to collect responses. Have you called `ob_start()`?');
        }
    }

    /**
     * Gets HTTP response data from output buffer and headers.
     *
     * Collects and processes:
     * - HTTP status code (defaults to 200 if not set)
     * - Response size in bytes from output buffer
     * - Response load time in milliseconds
     * - Response body as JSON (masked for sensitive fields)
     * - Response headers (filtered)
     *
     * Handles edge cases:
     * - Responses over 2MB: Logs error and returns empty body
     * - Invalid JSON: Logs error and returns empty body
     *
     * @return Response The response data transfer object
     */
    public function getResponse(): Response
    {
        $responseSize = ob_get_length() ?: 0;
        $responseBody = $this->getResponseBody($responseSize);

        // Only mask if body is not empty
        $responseBody = empty($responseBody) ? [] : $this->fieldMasker->mask($responseBody);

        $responseCode = http_response_code() ?: null;

        $headers = $this->getResponseHeaders();

        // Avoid function call if no headers to filter
        $filteredHeaders = empty($this->excludedHeaders)
            ? $headers
            : HeaderFilter::filter($headers, $this->excludedHeaders);

        return new Response(
            code: is_int($responseCode) ? $responseCode : 200,
            size: $responseSize,
            load_time: $this->getLoadTimeInMilliseconds(),
            body: $responseBody,
            headers: $filteredHeaders,
        );
    }

    /**
     * Gets response headers from headers_list().
     *
     * Parses headers returned by headers_list() into a key-value array.
     * Handles headers with colons in their values by only splitting on
     * the first colon.
     *
     * @return array<string, string> The response headers
     */
    private function getResponseHeaders(): array
    {
        $headers = headers_list();

        // Early return for empty headers
        if (empty($headers)) {
            return [];
        }

        $data = [];
        foreach ($headers as $header) {
            // Split only on first colon for better performance
            $pos = mb_strpos($header, ':');
            if (false !== $pos) {
                $key = mb_substr($header, 0, $pos);
                $value = trim(mb_substr($header, $pos + 1));
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Calculates the response load time in milliseconds.
     *
     * Measures the time between request start (REQUEST_TIME_FLOAT) and
     * the current time using microtime(true). Returns 0 if REQUEST_TIME_FLOAT
     * is not available.
     *
     * @return float The load time in milliseconds, or 0 if unavailable
     */
    private function getLoadTimeInMilliseconds(): float
    {
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            return (microtime(true) * 1000) - ((float)$_SERVER['REQUEST_TIME_FLOAT'] * 1000);
        }

        return 0.0000;
    }

    /**
     * Extracts and decodes the response body from output buffer.
     *
     * Handles special cases:
     * - Responses >= 2MB: Logs an error and returns empty array
     * - Invalid JSON: Logs an error and returns empty array
     * - Non-string output: Returns empty array
     * - Empty responses: Returns empty array immediately
     *
     * Uses ob_get_flush() to retrieve buffered output and attempts
     * to decode it as JSON.
     *
     * @param int $responseSize The size of the buffered output in bytes
     * @return array<int|string, mixed> The decoded response body or empty array
     */
    private function getResponseBody(int $responseSize): array
    {
        // Early return for empty or oversized responses
        if (0 === $responseSize) {
            return [];
        }

        if ($responseSize >= 2_000_000) {
            $this->errorDataProvider->addError(
                new Error(
                    'JSON response size is over 2MB',
                    '',
                    0,
                    'onShutdown',
                    'E_USER_ERROR',
                )
            );

            return [];
        }

        try {
            $output = ob_get_flush();
            if (! is_string($output) || '' === $output) {
                return [];
            }

            $decoded = json_decode($output, true);

            // Return empty array if JSON decoding failed or result is not an array
            return is_array($decoded) ? $decoded : [];
        } catch (Exception $exception) {
            $this->errorDataProvider->addError(
                new Error(
                    'Invalid JSON format: ' . $exception->getMessage(),
                    '',
                    0,
                    'onShutdown',
                    'INVALID_JSON',
                )
            );
        }

        return [];
    }
}
