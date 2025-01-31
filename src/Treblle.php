<?php

declare(strict_types=1);

namespace Treblle;

use Throwable;
use GuzzleHttp\ClientInterface;
use Treblle\DataTransferObject\Data;
use Treblle\DataTransferObject\Error;
use Treblle\Contract\ErrorDataProvider;
use Treblle\Contract\ServerDataProvider;
use Treblle\Contract\RequestDataProvider;
use Treblle\Contract\LanguageDataProvider;
use Treblle\Contract\ResponseDataProvider;

/**
 * Create a FREE Treblle account => https://treblle.com/register.
 */
final class Treblle
{
    private const SDK_VERSION = 0.8;
    private const SDK_NAME = 'php';

    /**
     * Create a new Treblle instance.
     */
    public function __construct(
        private string $apiKey,
        private string $projectId,
        private ClientInterface $client,
        private ServerDataProvider $serverDataProvider,
        private LanguageDataProvider $languageDataProvider,
        private RequestDataProvider $requestDataProvider,
        private ResponseDataProvider $responseDataProvider,
        private ErrorDataProvider $errorDataProvider,
        private bool $debug,
        private ?string $url = null,
        private bool $forkProcess = false
    ) {
    }

    /**
     * Capture PHP errors.
     */
    public function onError(int $type, string $message, string $file, int $line): bool
    {
        try {
            $this->errorDataProvider->addError(new Error(
                $message,
                $file,
                $line,
                'onError',
                ErrorHelper::translateErrorType($type),
            ));
        } catch (Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }
        }

        return false;
    }

    /**
     * Capture PHP exceptions.
     */
    public function onException(Throwable $exception): void
    {
        try {
            $this->errorDataProvider->addError(new Error(
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                'onException',
                'UNHANDLED_EXCEPTION',
            ));
        } catch (Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }
        }
    }

    /**
     * Process the log when PHP is finished processing.
     *
     * @throws Throwable
     */
    public function onShutdown(): void
    {
        try {
            $payload = $this->buildPayload();
            $payload = json_encode($payload);
        } catch (Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }

            $payload = '{"error": "could not convert payload to valid json in sdk"}';
        }

        if (! function_exists('pcntl_fork') || false === $this->forkProcess) {
            $this->collectData($payload);

            return;
        }

        $pid = pcntl_fork();

        if ($this->isUnableToForkProcess($pid)) {
            $this->collectData($payload);

            return;
        }

        if ($this->isChildProcess($pid)) {
            $this->collectData($payload);
            $this->killProcessWithId((int) getmypid());
        }
    }

    public function getBaseUrl(): string
    {
        $urls = [
            'https://rocknrolla.treblle.com',
            'https://punisher.treblle.com',
            'https://sicario.treblle.com',
        ];

        return $this->url ?? $urls[array_rand($urls)];
    }

    /**
     * @return array<int|string, mixed>
     *
     * @throws Throwable
     */
    private function buildPayload(): array
    {
        try {
            return [
                'api_key' => $this->apiKey,
                'project_id' => $this->projectId,
                'sdk' => self::SDK_NAME,
                'version' => self::SDK_VERSION,
                'data' => new Data(
                    $this->serverDataProvider->getServer(),
                    $this->languageDataProvider->getLanguage(),
                    $this->requestDataProvider->getRequest(),
                    $this->responseDataProvider->getResponse(),
                    $this->errorDataProvider->getErrors()
                ),
            ];
        } catch (Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }
        }

        return [];
    }

    private function collectData(string $payload): void
    {
        try {
            $this->client->request(
                'POST',
                $this->getBaseUrl(),
                [
                    'connect_timeout' => 3,
                    'timeout' => 3,
                    'verify' => false,
                    'http_errors' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'x-api-key' => $this->apiKey,
                    ],
                    'body' => $payload,
                ]
            );
        } catch (Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }
        }
    }

    private function isChildProcess(int $pid): bool
    {
        return 0 === $pid;
    }

    private function isUnableToForkProcess(int $pid): bool
    {
        return -1 === $pid;
    }

    private function killProcessWithId(int $pid): void
    {
        'WIN' === mb_strtoupper(mb_substr(PHP_OS, 0, 3)) ? exec("taskkill /F /T /PID {$pid}") : exec("kill -9 {$pid}");
    }
}
