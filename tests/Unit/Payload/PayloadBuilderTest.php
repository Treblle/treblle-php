<?php

declare(strict_types=1);

namespace Treblle\Php\Tests\Unit\Payload;

use PHPUnit\Framework\TestCase;
use Treblle\Php\Config\TreblleConfig;
use Treblle\Php\DataCollector\ErrorCollector;
use Treblle\Php\DataCollector\LanguageCollector;
use Treblle\Php\DataCollector\RequestCollector;
use Treblle\Php\DataCollector\ResponseCollector;
use Treblle\Php\DataCollector\ServerCollector;
use Treblle\Php\Masking\SensitiveDataMasker;
use Treblle\Php\Payload\PayloadBuilder;

class PayloadBuilderTest extends TestCase
{
    private function buildPayload(array $configOptions = []): array|false
    {
        $config = new TreblleConfig(
            sdkToken: $configOptions['sdkToken'] ?? 'test-sdk-token',
            apiKey: $configOptions['apiKey'] ?? 'test-api-key',
            maskedKeywords: $configOptions['maskedKeywords'] ?? [],
        );

        $masker = new SensitiveDataMasker($config->maskedKeywords);

        $requestCollector = $this->createMock(RequestCollector::class);
        $requestCollector->method('collect')->willReturn([
            'timestamp' => '2024-01-01 00:00:00',
            'ip' => '127.0.0.1',
            'url' => 'https://example.com/api/users',
            'user_agent' => 'TestAgent/1.0',
            'method' => 'GET',
            'headers' => ['Content-Type' => 'application/json'],
            'body' => $configOptions['requestBody'] ?? [],
            'route_path' => null,
            'query' => [],
        ]);

        $responseCollector = $this->createMock(ResponseCollector::class);
        $responseCollector->method('collect')->willReturn([
            'headers' => ['Content-Type' => 'application/json'],
            'code' => 200,
            'size' => 42,
            'load_time' => 12.5,
            'body' => $configOptions['responseBody'] ?? ['status' => 'ok'],
            'content_type' => 'application/json',
        ]);

        $serverCollector = $this->createMock(ServerCollector::class);
        $serverCollector->method('collect')->willReturn([
            'ip' => '10.0.0.1',
            'timezone' => 'UTC',
            'software' => 'nginx',
            'protocol' => 'HTTP/1.1',
            'os' => ['name' => 'Linux', 'release' => '5.15', 'architecture' => 'x86_64'],
        ]);

        $builder = new PayloadBuilder(
            config: $config,
            masker: $masker,
            server: $serverCollector,
            language: new LanguageCollector(),
            request: $requestCollector,
            response: $responseCollector,
            errors: new ErrorCollector(),
        );

        $gzipped = $builder->build();

        if ($gzipped === false) {
            return false;
        }

        $json = gzdecode($gzipped);
        self::assertNotFalse($json);

        return json_decode($json, true);
    }

    public function testPayloadHasRequiredTopLevelKeys(): void
    {
        $payload = $this->buildPayload();

        self::assertIsArray($payload);
        self::assertArrayHasKey('api_key', $payload);
        self::assertArrayHasKey('project_id', $payload);
        self::assertArrayHasKey('sdk', $payload);
        self::assertArrayHasKey('version', $payload);
        self::assertArrayHasKey('data', $payload);
    }

    public function testPayloadMapsCredentialsCorrectly(): void
    {
        $payload = $this->buildPayload([
            'sdkToken' => 'my-sdk-token',
            'apiKey' => 'my-api-key',
        ]);

        self::assertIsArray($payload);
        // sdkToken → api_key in payload
        self::assertSame('my-sdk-token', $payload['api_key']);
        // apiKey → project_id in payload
        self::assertSame('my-api-key', $payload['project_id']);
    }

    public function testPayloadSdkIdentifier(): void
    {
        $payload = $this->buildPayload();

        self::assertIsArray($payload);
        self::assertSame('php', $payload['sdk']);
        self::assertSame(60, $payload['version']);
    }

    public function testPayloadDataStructure(): void
    {
        $payload = $this->buildPayload();

        self::assertIsArray($payload);
        $data = $payload['data'];
        self::assertArrayHasKey('server', $data);
        self::assertArrayHasKey('language', $data);
        self::assertArrayHasKey('request', $data);
        self::assertArrayHasKey('response', $data);
        self::assertArrayHasKey('errors', $data);
    }

    public function testPayloadRequestFields(): void
    {
        $payload = $this->buildPayload();

        self::assertIsArray($payload);
        $request = $payload['data']['request'];

        self::assertSame('2024-01-01 00:00:00', $request['timestamp']);
        self::assertSame('127.0.0.1', $request['ip']);
        self::assertSame('https://example.com/api/users', $request['url']);
        self::assertSame('GET', $request['method']);
        self::assertNull($request['route_path']);
    }

    public function testPayloadResponseFields(): void
    {
        $payload = $this->buildPayload();

        self::assertIsArray($payload);
        $response = $payload['data']['response'];

        self::assertSame(200, $response['code']);
        self::assertSame(42, $response['size']);
        self::assertSame(12.5, $response['load_time']);
    }

    public function testSensitiveFieldsAreMaskedInPayload(): void
    {
        $payload = $this->buildPayload([
            'maskedKeywords' => ['password'],
            'requestBody' => ['username' => 'alice', 'password' => 'secret'],
        ]);

        self::assertIsArray($payload);
        $body = $payload['data']['request']['body'];

        self::assertSame('alice', $body['username']);
        self::assertSame('******', $body['password']);
    }

    public function testLanguageSection(): void
    {
        $payload = $this->buildPayload();

        self::assertIsArray($payload);
        $language = $payload['data']['language'];

        self::assertSame('php', $language['name']);
        self::assertSame(PHP_VERSION, $language['version']);
    }

    public function testPayloadIsGzipCompressed(): void
    {
        $config = new TreblleConfig('sdk-token', 'api-key');
        $masker = new SensitiveDataMasker([]);

        $requestCollector = $this->createMock(RequestCollector::class);
        $requestCollector->method('collect')->willReturn([
            'timestamp' => '2024-01-01 00:00:00',
            'ip' => '127.0.0.1',
            'url' => 'https://example.com/api',
            'user_agent' => '',
            'method' => 'GET',
            'headers' => [],
            'body' => [],
            'route_path' => null,
            'query' => [],
        ]);

        $responseCollector = $this->createMock(ResponseCollector::class);
        $responseCollector->method('collect')->willReturn([
            'headers' => [],
            'code' => 200,
            'size' => 0,
            'load_time' => 0.0,
            'body' => [],
            'content_type' => null,
        ]);

        $serverCollector = $this->createMock(ServerCollector::class);
        $serverCollector->method('collect')->willReturn([
            'ip' => 'bogon',
            'timezone' => 'UTC',
            'software' => null,
            'protocol' => null,
            'os' => ['name' => null, 'release' => null, 'architecture' => null],
        ]);

        $builder = new PayloadBuilder(
            config: $config,
            masker: $masker,
            server: $serverCollector,
            language: new LanguageCollector(),
            request: $requestCollector,
            response: $responseCollector,
            errors: new ErrorCollector(),
        );

        $result = $builder->build();

        self::assertIsString($result);
        // Verify it's valid gzip
        $decoded = gzdecode($result);
        self::assertNotFalse($decoded);
        $json = json_decode($decoded, true);
        self::assertIsArray($json);
    }
}
