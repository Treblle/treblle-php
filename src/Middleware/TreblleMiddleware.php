<?php

declare(strict_types=1);

namespace Treblle\Php\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Treblle\Php\Config\TreblleConfig;
use Treblle\Php\DataCollector\ErrorCollector;
use Treblle\Php\DataCollector\LanguageCollector;
use Treblle\Php\DataCollector\RequestCollector;
use Treblle\Php\DataCollector\ServerCollector;
use Treblle\Php\Filter\PathMatcher;
use Treblle\Php\Filter\RequestTypeFilter;
use Treblle\Php\Http\IngressClient;
use Treblle\Php\Masking\SensitiveDataMasker;
use Treblle\Php\Transport\AsyncTransport;
use Treblle\Php\Treblle;

class TreblleMiddleware implements MiddlewareInterface
{
    private readonly SensitiveDataMasker $masker;
    private readonly PathMatcher $pathMatcher;
    private readonly RequestTypeFilter $requestTypeFilter;
    private readonly AsyncTransport $transport;
    private readonly ServerCollector $serverCollector;
    private readonly LanguageCollector $languageCollector;

    public function __construct(private readonly TreblleConfig $config)
    {
        $this->masker = new SensitiveDataMasker($config->maskedKeywords);
        $this->pathMatcher = new PathMatcher($config->excludedPaths);
        $this->requestTypeFilter = new RequestTypeFilter();
        $this->transport = new AsyncTransport(
            client: new IngressClient(),
            debug: $config->debug,
        );
        $this->serverCollector = new ServerCollector();
        $this->languageCollector = new LanguageCollector();
    }

    /**
     * Convenience factory — mirrors Treblle::create() signature.
     *
     * @param array{
     *   debug?: bool,
     *   masked_keywords?: string[],
     *   excluded_paths?: string[],
     *   custom_ingress?: string,
     *   enabled?: bool,
     * } $options
     */
    public static function create(string $sdkToken, string $apiKey, array $options = []): self
    {
        return new self(new TreblleConfig(
            sdkToken: $sdkToken,
            apiKey: $apiKey,
            debug: (bool) ($options['debug'] ?? false),
            maskedKeywords: array_values($options['masked_keywords'] ?? TreblleConfig::DEFAULT_MASKED_KEYWORDS),
            excludedPaths: array_values($options['excluded_paths'] ?? []),
            customIngress: $options['custom_ingress'] ?? null,
            enabled: (bool) ($options['enabled'] ?? true),
        ));
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! $this->config->enabled || $this->config->sdkToken === '' || $this->config->apiKey === '') {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();

        if ($this->pathMatcher->matches($path)) {
            if ($this->config->debug) {
                error_log("[TREBLLE] Request excluded by path: {$path}");
            }

            return $handler->handle($request);
        }

        $startTime = microtime(true);
        $errorCollector = new ErrorCollector();

        try {
            $response = $handler->handle($request);
        } catch (\Throwable $e) {
            $errorCollector->addError('onException', 'UNHANDLED_EXCEPTION', $e->getMessage(), $e->getFile(), $e->getLine());

            throw $e;
        }

        $uri = (string) $request->getUri();
        $contentType = $response->getHeaderLine('Content-Type');

        if ($this->requestTypeFilter->shouldSkip($uri, $contentType)) {
            if ($this->config->debug) {
                error_log("[TREBLLE] Skipping non-REST request: {$uri}");
            }

            return $response;
        }

        $payload = $this->buildPayload($request, $response, $errorCollector, $startTime);

        if ($payload !== false) {
            $endpoint = $this->config->getIngressUrl();
            $sdkToken = $this->config->sdkToken;
            $transport = $this->transport;
            $debug = $this->config->debug;

            register_shutdown_function(static function () use ($payload, $endpoint, $sdkToken, $transport, $debug): void {
                try {
                    $transport->send($payload, $endpoint, $sdkToken);
                } catch (\Throwable $e) {
                    if ($debug) {
                        error_log('[TREBLLE] Failed to send payload: ' . $e->getMessage());
                    }
                }
            });
        }

        return $response;
    }

    private function buildPayload(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ErrorCollector $errors,
        float $startTime,
    ): string|false {
        $requestData = $this->extractRequestData($request);
        $responseData = $this->extractResponseData($response, $startTime);
        $metadata = Treblle::getMetadata();

        $data = [
            'server' => $this->serverCollector->collect(),
            'language' => $this->languageCollector->collect(),
            'request' => [
                'timestamp' => $requestData['timestamp'],
                'ip' => $requestData['ip'],
                'url' => $requestData['url'],
                'user_agent' => $requestData['user_agent'],
                'method' => $requestData['method'],
                'headers' => $this->masker->maskHeaders($requestData['headers']),
                'body' => $this->masker->maskData($requestData['body']),
                'route_path' => $requestData['route_path'],
                'query' => $this->masker->maskData($requestData['query']),
            ],
            'response' => [
                'headers' => $this->masker->maskHeaders($responseData['headers']),
                'code' => $responseData['code'],
                'size' => $responseData['size'],
                'load_time' => $responseData['load_time'],
                'body' => $this->masker->maskData($responseData['body']),
            ],
            'errors' => $errors->getErrors(),
        ];

        if ($metadata !== []) {
            $data['metadata'] = $metadata;
        }

        $payload = [
            'api_key' => $this->config->sdkToken,
            'project_id' => $this->config->apiKey,
            'sdk' => TreblleConfig::SDK_NAME,
            'version' => TreblleConfig::SDK_VERSION,
            'data' => $data,
        ];

        try {
            $json = json_encode(
                $payload,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
            );
        } catch (\JsonException) {
            return false;
        }

        unset($payload);

        $gzipped = gzencode($json, 1);

        return $gzipped === false ? false : $gzipped;
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
     *   query: array<string, mixed>
     * }
     */
    private function extractRequestData(ServerRequestInterface $request): array
    {
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        // Route path: check PSR-7 attribute first, then fall back to Treblle static state
        $routePath = $request->getAttribute('_treblle_route_path');
        if (! is_string($routePath) || $routePath === '') {
            $routePath = RequestCollector::getRoutePath();
        }

        return [
            'timestamp' => gmdate('Y-m-d H:i:s'),
            'ip' => $this->resolveClientIp($request),
            'url' => (string) $request->getUri(),
            'user_agent' => $request->getHeaderLine('User-Agent'),
            'method' => strtoupper($request->getMethod()),
            'headers' => $headers,
            'body' => $this->parseRequestBody($request),
            'route_path' => is_string($routePath) && $routePath !== '' ? $routePath : null,
            'query' => $request->getQueryParams(),
        ];
    }

    /**
     * @return array{
     *   headers: array<string, string>,
     *   code: int,
     *   size: int,
     *   load_time: float,
     *   body: array<string, mixed>
     * }
     */
    private function extractResponseData(ResponseInterface $response, float $startTime): array
    {
        $body = $response->getBody();
        $rawBody = (string) $body;

        if ($body->isSeekable()) {
            $body->rewind();
        }

        $size = mb_strlen($rawBody, '8bit');

        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        return [
            'headers' => $headers,
            'code' => $response->getStatusCode(),
            'size' => $size,
            'load_time' => round((microtime(true) - $startTime) * 1000, 2),
            'body' => $this->parseResponseBody($rawBody, $size),
        ];
    }

    /** @return array<string, mixed> */
    private function parseRequestBody(ServerRequestInterface $request): array
    {
        $contentType = strtolower($request->getHeaderLine('Content-Type'));

        // File uploads — capture metadata only
        $uploadedFiles = $request->getUploadedFiles();
        if (! empty($uploadedFiles)) {
            return $this->extractUploadedFilesMeta($uploadedFiles);
        }

        // Form data — framework has already parsed this
        if (str_contains($contentType, 'application/x-www-form-urlencoded') || str_contains($contentType, 'multipart/form-data')) {
            $parsed = $request->getParsedBody();

            return is_array($parsed) ? $parsed : [];
        }

        $body = $request->getBody();
        $rawBody = (string) $body;

        if ($body->isSeekable()) {
            $body->rewind();
        }

        $size = mb_strlen($rawBody, '8bit');

        if ($size > 2 * 1024 * 1024) {
            return ['error' => 'Payload exceeds 2MB', 'size' => $size];
        }

        if ($rawBody === '') {
            return [];
        }

        $data = json_decode($rawBody, true);

        return is_array($data) ? $data : ['error' => 'Request payload is not valid JSON'];
    }

    /** @return array<string, mixed> */
    private function parseResponseBody(string $rawBody, int $size): array
    {
        if ($rawBody === '') {
            return [];
        }

        if ($size > 2 * 1024 * 1024) {
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

    private function resolveClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();

        $clientIp = $serverParams['HTTP_CLIENT_IP'] ?? null;
        if (is_string($clientIp) && $clientIp !== '') {
            return trim(explode(',', $clientIp)[0]);
        }

        $forwardedFor = $serverParams['HTTP_X_FORWARDED_FOR'] ?? null;
        if (is_string($forwardedFor) && $forwardedFor !== '') {
            return trim(explode(',', $forwardedFor)[0]);
        }

        $remoteAddr = $serverParams['REMOTE_ADDR'] ?? null;
        if (is_string($remoteAddr) && $remoteAddr !== '') {
            return $remoteAddr;
        }

        return 'bogon';
    }

    /**
     * @param array<string, UploadedFileInterface|array<mixed>> $files
     * @return array<string, mixed>
     */
    private function extractUploadedFilesMeta(array $files): array
    {
        $uploads = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFileInterface) {
                $uploads[] = [
                    'name' => $file->getClientFilename(),
                    'type' => $file->getClientMediaType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        if ($uploads === []) {
            return [];
        }

        return ['_treblle_file_upload' => count($uploads) === 1 ? $uploads[0] : $uploads];
    }
}
