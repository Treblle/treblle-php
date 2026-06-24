<?php

declare(strict_types=1);

namespace Treblle\Php\Payload;

use Treblle\Php\Config\TreblleConfig;
use Treblle\Php\DataCollector\ErrorCollector;
use Treblle\Php\DataCollector\LanguageCollector;
use Treblle\Php\DataCollector\RequestCollector;
use Treblle\Php\DataCollector\ResponseCollector;
use Treblle\Php\DataCollector\ServerCollector;
use Treblle\Php\Masking\SensitiveDataMasker;

class PayloadBuilder
{
    public function __construct(
        private readonly TreblleConfig $config,
        private readonly SensitiveDataMasker $masker,
        private readonly ServerCollector $server,
        private readonly LanguageCollector $language,
        private readonly RequestCollector $request,
        private readonly ResponseCollector $response,
        private readonly ErrorCollector $errors,
    ) {
    }

    /**
     * Builds, JSON-encodes, and gzip-compresses the payload.
     * Returns false if the payload is empty or encoding fails.
     *
     * @param array<string, mixed> $metadata
     */
    public function build(array $metadata = []): string|false
    {
        $request = $this->request->collect();
        $response = $this->response->collect();

        $data = [
            'server' => $this->server->collect(),
            'language' => $this->language->collect(),
            'request' => [
                'timestamp' => $request['timestamp'],
                'ip' => $request['ip'],
                'url' => $request['url'],
                'user_agent' => $request['user_agent'],
                'method' => $request['method'],
                'headers' => $this->masker->maskHeaders($request['headers']),
                'body' => $this->masker->maskData($request['body']),
                'route_path' => $request['route_path'],
                'query' => $this->masker->maskData($request['query']),
            ],
            'response' => [
                'headers' => $this->masker->maskHeaders($response['headers']),
                'code' => $response['code'],
                'size' => $response['size'],
                'load_time' => $response['load_time'],
                'body' => $this->masker->maskData($response['body']),
            ],
            'errors' => $this->errors->getErrors(),
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
}
