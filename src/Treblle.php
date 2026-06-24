<?php

declare(strict_types=1);

namespace Treblle\Php;

use Treblle\Php\Config\TreblleConfig;
use Treblle\Php\DataCollector\ErrorCollector;
use Treblle\Php\DataCollector\LanguageCollector;
use Treblle\Php\DataCollector\RequestCollector;
use Treblle\Php\DataCollector\ResponseCollector;
use Treblle\Php\DataCollector\ServerCollector;
use Treblle\Php\Filter\PathMatcher;
use Treblle\Php\Filter\RequestTypeFilter;
use Treblle\Php\Http\IngressClient;
use Treblle\Php\Masking\SensitiveDataMasker;
use Treblle\Php\Payload\PayloadBuilder;
use Treblle\Php\Transport\AsyncTransport;

class Treblle
{
    private static bool $initialized = false;

    /** @var array<string, mixed> */
    private static array $metadata = [];

    private readonly ErrorCollector $errorCollector;
    private readonly RequestCollector $requestCollector;
    private readonly ResponseCollector $responseCollector;
    private readonly PayloadBuilder $payloadBuilder;
    private readonly AsyncTransport $transport;
    private readonly PathMatcher $pathMatcher;
    private readonly RequestTypeFilter $requestTypeFilter;

    private function __construct(private readonly TreblleConfig $config)
    {
        $masker = new SensitiveDataMasker($config->maskedKeywords);

        $this->errorCollector = new ErrorCollector();
        $this->requestCollector = new RequestCollector();
        $this->responseCollector = new ResponseCollector();
        $this->pathMatcher = new PathMatcher($config->excludedPaths);
        $this->requestTypeFilter = new RequestTypeFilter();

        $this->payloadBuilder = new PayloadBuilder(
            config: $config,
            masker: $masker,
            server: new ServerCollector(),
            language: new LanguageCollector(),
            request: $this->requestCollector,
            response: $this->responseCollector,
            errors: $this->errorCollector,
        );

        $this->transport = new AsyncTransport(
            client: new IngressClient(),
            debug: $config->debug,
        );

        if (! $config->enabled) {
            return;
        }

        // Buffer output so we can capture the response body
        ob_start();

        $this->registerHandlers();
    }

    /**
     * Initialize Treblle with a pre-built config object.
     */
    public static function start(TreblleConfig $config): void
    {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;

        new self($config);
    }

    /**
     * Convenience factory — builds TreblleConfig from raw values.
     *
     * @param array{
     *   debug?: bool,
     *   masked_keywords?: string[],
     *   excluded_paths?: string[],
     *   custom_ingress?: string,
     *   enabled?: bool,
     * } $options
     */
    public static function create(string $sdkToken, string $apiKey, array $options = []): void
    {
        self::start(new TreblleConfig(
            sdkToken: $sdkToken,
            apiKey: $apiKey,
            debug: (bool) ($options['debug'] ?? false),
            maskedKeywords: array_values($options['masked_keywords'] ?? TreblleConfig::DEFAULT_MASKED_KEYWORDS),
            excludedPaths: array_values($options['excluded_paths'] ?? []),
            customIngress: $options['custom_ingress'] ?? null,
            enabled: (bool) ($options['enabled'] ?? true),
        ));
    }

    private function registerHandlers(): void
    {
        $collector = $this->errorCollector;
        $debug = $this->config->debug;

        set_error_handler(
            static function (int $errno, string $errstr, string $errfile, int $errline) use ($collector): bool {
                $collector->addError('onError', ErrorTypeTranslator::translate($errno), $errstr, $errfile, $errline);

                // Return false to allow PHP's internal error handler to run as well
                return false;
            }
        );

        set_exception_handler(
            static function (\Throwable $e) use ($collector, $debug): void {
                $collector->addError(
                    'onException',
                    'UNHANDLED_EXCEPTION',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                );

                if ($debug) {
                    error_log('[TREBLLE] Unhandled exception captured: ' . $e->getMessage());
                }
            }
        );

        register_shutdown_function(function (): void {
            $this->handleShutdown();
        });
    }

    private function handleShutdown(): void
    {
        // Capture fatal errors that PHP itself handles at shutdown
        $lastError = error_get_last();
        if ($lastError !== null && in_array($lastError['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $this->errorCollector->addError(
                'onShutdown',
                ErrorTypeTranslator::translate($lastError['type']),
                $lastError['message'],
                $lastError['file'],
                $lastError['line']
            );
        }

        if (! $this->config->enabled) {
            if ($this->config->debug) {
                error_log('[TREBLLE] Treblle is disabled');
            }
            $this->flushOutput();

            return;
        }

        if ($this->config->sdkToken === '' || $this->config->apiKey === '') {
            $this->flushOutput();

            return;
        }

        $serverUri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = is_string($serverUri) ? $serverUri : '/';
        $parsed = parse_url($uri, PHP_URL_PATH);
        $path = is_string($parsed) ? $parsed : $uri;

        if ($this->pathMatcher->matches($path)) {
            if ($this->config->debug) {
                error_log("[TREBLLE] Request excluded by path: {$path}");
            }
            $this->flushOutput();

            return;
        }

        // Collect response data then immediately flush — response body is captured above,
        // freeing the ob buffer before the heavier payload build step below.
        $response = $this->responseCollector->collect();
        $contentType = $response['content_type'];
        $this->flushOutput();

        if ($this->requestTypeFilter->shouldSkip($uri, $contentType)) {
            if ($this->config->debug) {
                error_log("[TREBLLE] Skipping non-REST request: {$uri}");
            }

            return;
        }

        $payload = $this->payloadBuilder->build(self::$metadata);

        if ($payload === false) {
            return;
        }

        $endpoint = $this->config->getIngressUrl();

        try {
            $this->transport->send($payload, $endpoint, $this->config->sdkToken);
        } catch (\Throwable $e) {
            if ($this->config->debug) {
                error_log('[TREBLLE] Failed to send payload: ' . $e->getMessage());
            }
        }
    }

    private function flushOutput(): void
    {
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    /**
     * Attach custom key/value pairs to the current request payload under `data.metadata`.
     * Call this anywhere during the request lifecycle — the data is read at shutdown.
     * Multiple calls are merged together.
     *
     * @param array<string, mixed> $metadata
     */
    public static function metadata(array $metadata): void
    {
        self::$metadata = array_merge(self::$metadata, $metadata);
    }

    /** @return array<string, mixed> */
    public static function getMetadata(): array
    {
        return self::$metadata;
    }

    /**
     * Set the parameterised route path for the current request (e.g. `articles/{id}`).
     *
     * Plain PHP has no built-in router, so the SDK cannot infer the route pattern
     * automatically. Call this after your router has resolved the route:
     *
     *   Treblle::setRoutePath('articles/{id}');
     *
     * Takes priority over the $_SERVER['TREBLLE_ROUTE_PATH'] fallback.
     */
    public static function setRoutePath(string $path): void
    {
        RequestCollector::setRoutePath($path);
    }

    /**
     * Reset the initialized flag so Treblle can be re-initialized on the next request.
     * Call this at the start of each request cycle in persistent runtimes
     * (Swoole, RoadRunner, FrankenPHP, ReactPHP).
     */
    public static function reset(): void
    {
        self::$initialized = false;
        self::$metadata = [];
        RequestCollector::reset();
    }
}
