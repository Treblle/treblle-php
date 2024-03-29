<?php

declare(strict_types=1);

namespace Treblle;

use Safe\Exceptions\JsonException;
use Treblle\Contract\ErrorDataProvider;
use Treblle\Contract\ResponseDataProvider;
use Treblle\Model\Error;
use Treblle\Model\Response;

final class OutputBufferingResponseDataProvider implements ResponseDataProvider
{
    private PayloadAnonymizer $anonymizer;
    private ErrorDataProvider $errorDataProvider;

    public function __construct(PayloadAnonymizer $anonymizer, ErrorDataProvider $errorDataProvider)
    {
        if (ob_get_level() < 1) {
            throw new \RuntimeException('Output buffering must be enabled to collect responses. Have you called `ob_start()`?');
        }

        $this->anonymizer = $anonymizer;
        $this->errorDataProvider = $errorDataProvider;
    }

    public function getResponse(): Response
    {
        $responseSize = ob_get_length() ?: 0;
        $responseBody = $this->getResponseBody($responseSize);
        $responseBody = $this->anonymizer->annonymize($responseBody);
        $responseCode = http_response_code() ?: null;

        return new Response(
            $this->getResponseHeaders(),
            \is_int($responseCode) ? $responseCode : null,
            $responseSize,
            $this->getLoadTime(),
            $responseBody,
        );
    }

    /**
     * @return array<string, string>
     */
    private function getResponseHeaders(): array
    {
        $data = [];
        $headers = headers_list();

        if (\is_array($headers) && !empty($headers)) {
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
    private function getLoadTime(): float
    {
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            return (float) microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
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
                    'onShutdown',
                    'E_USER_ERROR',
                    'JSON response size is over 2MB',
                    null,
                    null,
                )
            );

            return [];
        }

        try {
            $output = ob_get_flush();
            if (!\is_string($output)) {
                return [];
            }

            return \Safe\json_decode($output, true);
        } catch (JsonException $exception) {
            $this->errorDataProvider->addError(
                new Error(
                    'onShutdown',
                    'INVALID_JSON',
                    'Invalid JSON format: '.$exception->getMessage(),
                    null,
                    null,
                )
            );
        }

        return [];
    }
}
