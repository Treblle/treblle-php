<?php

declare(strict_types=1);

namespace Treblle\Php;

use Exception;
use RuntimeException;
use Treblle\Php\DataTransferObject\Error;
use Treblle\Php\Contract\ErrorDataProvider;
use Treblle\Php\DataTransferObject\Response;
use Treblle\Php\Contract\ResponseDataProvider;

final class OutputBufferingResponseDataProvider implements ResponseDataProvider
{
    public function __construct(
        private FieldMasker       $fieldMasker,
        private ErrorDataProvider $errorDataProvider
    ) {
        if (ob_get_level() < 1) {
            throw new RuntimeException('Output buffering must be enabled to collect responses. Have you called `ob_start()`?');
        }
    }

    public function getResponse(): Response
    {
        $responseSize = ob_get_length() ?: 0;
        $responseBody = $this->getResponseBody($responseSize);
        $responseBody = $this->fieldMasker->mask($responseBody);
        $responseCode = http_response_code() ?: null;

        return new Response(
            code: is_int($responseCode) ? $responseCode : 200,
            size: $responseSize,
            load_time: $this->getLoadTimeInMilliseconds(),
            body: $responseBody,
            headers: $this->getResponseHeaders(),
        );
    }

    /**
     * @return array<string, string>
     */
    private function getResponseHeaders(): array
    {
        $data = [];
        $headers = headers_list();

        if (is_array($headers) && ! empty($headers)) {
            foreach ($headers as $header) {
                $header = explode(':', $header);
                $data[array_shift($header)] = trim(implode(':', $header));
            }
        }

        return $data;
    }

    /**
     * Calculate the execution time for the script.
     */
    private function getLoadTimeInMilliseconds(): float
    {
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            return (microtime(true) * 1000) - ((float)$_SERVER['REQUEST_TIME_FLOAT'] * 1000);
        }

        return 0.0000;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function getResponseBody(int $responseSize): array
    {
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
            if (! is_string($output)) {
                return [];
            }

            return json_decode($output, true);
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
