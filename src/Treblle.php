<?php

declare(strict_types=1);

namespace Treblle;

use GuzzleHttp\ClientInterface;
use Treblle\Contract\ErrorDataProvider;
use Treblle\Contract\LanguageDataProvider;
use Treblle\Contract\RequestDataProvider;
use Treblle\Contract\ResponseDataProvider;
use Treblle\Contract\ServerDataProvider;
use Treblle\Model\Data;
use Treblle\Model\Error;

use function Safe\getmypid;

/**
 * Create a FREE Treblle account => https://treblle.com/register.
 */
class Treblle
{
    protected const SDK_VERSION = 0.8;
    protected const SDK_NAME = 'php';

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
        private array $ignore = [],
        private ?string $url = null
    ) {}

    /**
     * Capture PHP errors.
     */
    public function onError(int $type, string $message, string $file, int $line): bool
    {
        try {
            $this->errorDataProvider->addError(new Error(
                'onError',
                ErrorHelper::translateErrorType($type),
                $message,
                $file,
                $line,
            ));
        } catch (\Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }
        }

        return false;
    }

    /**
     * Capture PHP exceptions.
     */
    public function onException(\Throwable $exception): void
    {
        try {
            $this->errorDataProvider->addError(new Error(
                'onException',
                'UNHANDLED_EXCEPTION',
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
            ));
        } catch (\Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }
        }
    }

    /**
     * @return array<int|string, mixed>
     *
     * @throws \Throwable
     */
    private function buildPayload(): array
    {
        try {
            return [
                'api_key' => $this->apiKey,
                'project_id' => $this->projectId,
                'version' => self::SDK_VERSION,
                'sdk' => self::SDK_NAME,
                'data' => new Data(
                    $this->serverDataProvider->getServer(),
                    $this->languageDataProvider->getLanguage(),
                    $this->requestDataProvider->getRequest(),
                    $this->responseDataProvider->getResponse(),
                    $this->errorDataProvider->getErrors()
                ),
            ];
        } catch (\Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }
        }

        return [];
    }

    /**
     * Process the log when PHP is finished processing.
     *
     * @throws \Throwable
     */
    public function onShutdown(): void
    {
        try {
            $payload = $this->buildPayload();
            $payload = \Safe\json_encode($payload);
        } catch (\Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }

            /** @todo come up with some kind of fallback to be sent if we cannot convert array to json */
            $payload = '{}';
        }

        if (!\function_exists('pcntl_fork') || (\defined('ARE_TESTS_RUNNING') && ARE_TESTS_RUNNING)) {
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
        } catch (\Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }
        }
    }

    private function isChildProcess(int $pid): bool
    {
        return $pid === 0;
    }

    private function isUnableToForkProcess(int $pid): bool
    {
        return $pid === -1;
    }

    private function killProcessWithId(int $pid): void
    {
        mb_strtoupper(mb_substr(PHP_OS, 0, 3)) === 'WIN' ? exec("taskkill /F /T /PID {$pid}") : exec("kill -9 {$pid}");
    }

    public function ignoredUris(): array
    {
        return $this->ignore;
    }
}
