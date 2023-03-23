<?php

declare(strict_types=1);

namespace Treblle;

use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\RequestFactoryInterface;
use Safe\Exceptions\JsonException;
use Throwable;
use Treblle\Core\Contracts\DataProviders\ErrorContract;
use Treblle\Core\Contracts\DataProviders\LanguageContract;
use Treblle\Core\Contracts\DataProviders\RequestContract;
use Treblle\Core\Contracts\DataProviders\ResponseContract;
use Treblle\Core\Contracts\DataProviders\ServerContract;
use Treblle\Core\Contracts\Masking\MaskingContract;
use Treblle\Core\DataObjects\Data;
use Treblle\Core\DataObjects\Error;
use Treblle\Core\Http\Endpoint;
use Treblle\Core\Support\ErrorType;

/**
 * Create a FREE Treblle account => https://treblle.com/register.
 */
final class Treblle
{
    private const SDK_VERSION = 1.0;
    private const SDK_NAME = 'php';

    public HttpClient $client;
    public RequestFactoryInterface $requestFactory;

    /**
     * Create a new Treblle instance.
     *
     * @param string $apiKey
     * @param string $projectId
     * @param ServerContract $server
     * @param LanguageContract $language
     * @param RequestContract $request
     * @param ResponseContract $response
     * @param ErrorContract $error
     * @param MaskingContract $masker
     * @param bool $debug
     */
    public function __construct(
        private readonly string $apiKey,
        private readonly string $projectId,
        public readonly ServerContract $server,
        public readonly LanguageContract $language,
        public readonly RequestContract $request,
        public readonly ResponseContract $response,
        public readonly ErrorContract $error,
        public readonly MaskingContract $masker,
        private readonly bool $debug,
    ) {
        $this->client = HttpClientDiscovery::find();
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
    }

    /**
     * Capture PHP errors.
     *
     * @param int $type
     * @param string $message
     * @param string $file
     * @param int $line
     * @return void
     * @throws Throwable
     */
    public function onError(int $type, string $message, string $file, int $line): void
    {
        try {
            $this->error->add(
                error: new Error(
                    source: 'onError',
                    type: ErrorType::get(
                        type: $type,
                    ),
                    message: $message,
                    file: $file,
                    line: $line,
                ));
        } catch (Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }
        }
    }

    /**
     * Capture PHP exceptions.
     *
     * @param Throwable $exception
     * @return void
     * @throws Throwable
     */
    public function onException(Throwable $exception): void
    {
        try {
            $this->error->add(
                error: new Error(
                    source: 'onException',
                    type: ErrorType::get(
                        type: E_ERROR,
                    ),
                    message: $exception->getMessage(),
                    file: $exception->getFile(),
                    line: $exception->getLine(),
                ),
            );
        } catch (Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }
        }
    }

    /**
     * Build the request payload to send to Treblle.
     *
     * @throws Throwable
     * @return array<int|string, mixed>
     */
    public function buildPayload(): array
    {
        try {
            return [
                'api_key' => $this->apiKey,
                'project_id' => $this->projectId,
                'version' => self::SDK_VERSION,
                'sdk' => self::SDK_NAME,
                'data' => new Data(
                    $this->server->get(),
                    $this->language->get(),
                    $this->request->get(),
                    $this->response->get(),
                    $this->error->get()
                ),
            ];
        } catch (Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }
        }

        return [];
    }

    /**
     * Process the log when PHP is finished processing.
     *
     * @return void
     * @throws Throwable
     * @throws JsonException
     */
    public function onShutdown(): void
    {
        try {
            $payload = \Safe\json_encode(
                value: $this->masker->mask(
                    data: $this->buildPayload(),
                ),
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }

            /**
             * @todo come up with some kind of fallback to be sent if we cannot convert array to json
             */
            $payload = [];
        }

        try {
            $this->client->sendRequest(
                request: $this->requestFactory->createRequest(
                    'POST',
                    new Uri(
                        uri: Endpoint::PUNISHER->value,
                    ),
                    $payload,
                    [
                        'Content-Type' => 'application/json',
                        'x-api-key' => $this->apiKey,
                    ],
                ),
            );
        } catch (Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }
        }
    }

    public function setClient(HttpClient $client): Treblle
    {
        $this->client = $client;

        return $this;
    }
}
