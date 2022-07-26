<?php

declare(strict_types=1);

namespace Tests\Treblle\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use JsonSchema\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Treblle\Contract\ErrorDataProvider;
use Treblle\Contract\LanguageDataProvider;
use Treblle\Contract\RequestDataProvider;
use Treblle\Contract\ResponseDataProvider;
use Treblle\Contract\ServerDataProvider;
use Treblle\Model\Error;
use Treblle\Model\Language;
use Treblle\Model\Os;
use Treblle\Model\Request;
use Treblle\Model\Response;
use Treblle\Model\Server;
use Treblle\Treblle;

/**
 * @internal
 * @coversNothing
 *
 * @small
 */
final class TreblleTest extends TestCase
{
    private Validator $validator;

    private string $schemaPath;

    private MockHandler $mockHandler;

    private array $container = [];

    /** @var ServerDataProvider&MockObject */
    private ServerDataProvider $serverDataProvider;

    /** @var LanguageDataProvider&MockObject */
    private LanguageDataProvider $languageDataProvider;

    /** @var RequestDataProvider&MockObject */
    private RequestDataProvider $requestDataProvider;

    /** @var ResponseDataProvider&MockObject */
    private ResponseDataProvider $responseDataProvider;

    /** @var ErrorDataProvider&MockObject */
    private ErrorDataProvider $errorDataProvider;

    private Treblle $subjectUnderTest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new Validator();
        $this->schemaPath = __DIR__.'/../../schema/request.json';

        $this->mockHandler = new MockHandler([]);
        $history = Middleware::history($this->container);
        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $this->serverDataProvider = $this->createMock(ServerDataProvider::class);
        $this->languageDataProvider = $this->createMock(LanguageDataProvider::class);
        $this->requestDataProvider = $this->createMock(RequestDataProvider::class);
        $this->responseDataProvider = $this->createMock(ResponseDataProvider::class);
        $this->errorDataProvider = $this->createMock(ErrorDataProvider::class);

        $this->subjectUnderTest = new Treblle(
            'my api key',
            'my project id',
            $client,
            $this->serverDataProvider,
            $this->languageDataProvider,
            $this->requestDataProvider,
            $this->responseDataProvider,
            $this->errorDataProvider,
            true
        );
    }

    public function provideTestData(): iterable
    {
        yield 'request and response without errors' => [
            'server' => new Server(
                '1.1.1.1',
                'Europe/London',
                'My Software',
                'My Signature',
                'https',
                new Os('Ubuntu', '14.04', 'x86'),
                'utf-8'
            ),
            'language' => new Language('php', '8.0', 'Off', 'Off'),
            'request' => new Request(
                'YYYY-MM-DD hh:mm:ss',
                '8.8.8.8',
                'http://localhost/foo',
                'browser',
                'POST',
                ['Accept' => 'application/json', 'Content-type' => 'application/json'],
                ['foo' => 'bar'],
                ['baz' => 'bash'],
            ),
            'response' => new Response(
                ['Accept' => 'application/json', 'Content-type' => 'application/json'],
                200,
                1024 * 8,
                0.15,
                ['status' => 'ok'],
            ),
            'errors' => [],
            'expectedRequest' => [
                'api_key' => 'my api key',
                'project_id' => 'my project id',
                'version' => 0.8,
                'sdk' => 'php',
                'data' => [
                    'server' => [
                        'ip' => '1.1.1.1',
                        'timezone' => 'Europe/London',
                        'software' => 'My Software',
                        'signature' => 'My Signature',
                        'protocol' => 'https',
                        'os' => [
                            'name' => 'Ubuntu',
                            'release' => '14.04',
                            'architecture' => 'x86',
                        ],
                        'encoding' => 'utf-8',
                    ],
                    'language' => [
                        'name' => 'php',
                        'version' => '8.0',
                        'expose_php' => 'Off',
                        'display_errors' => 'Off',
                    ],
                    'request' => [
                        'timestamp' => 'YYYY-MM-DD hh:mm:ss',
                        'ip' => '8.8.8.8',
                        'url' => 'http://localhost/foo',
                        'user_agent' => 'browser',
                        'method' => 'POST',
                        'headers' => [
                            'Accept' => 'application/json',
                            'Content-type' => 'application/json',
                        ],
                        'body' => [
                            'foo' => 'bar',
                        ],
                        'raw' => [
                            'baz' => 'bash',
                        ],
                    ],
                    'response' => [
                        'headers' => [
                            'Accept' => 'application/json',
                            'Content-type' => 'application/json',
                        ],
                        'code' => 200,
                        'size' => 8192,
                        'load_time' => 0.15,
                        'body' => [
                            'status' => 'ok',
                        ],
                    ],
                    'errors' => [],
                ],
            ],
        ];

        yield 'request and response with a single error' => [
            'server' => new Server(
                '1.1.1.1',
                'Europe/London',
                'My Software',
                'My Signature',
                'https',
                new Os('Ubuntu', '14.04', 'x86'),
                'utf-8'
            ),
            'language' => new Language('php', '8.0', 'Off', 'Off'),
            'request' => new Request(
                'YYYY-MM-DD hh:mm:ss',
                '8.8.8.8',
                'http://localhost/foo',
                'browser',
                'POST',
                ['Accept' => 'application/json', 'Content-type' => 'application/json'],
                ['foo' => 'bar'],
                ['baz' => 'bash'],
            ),
            'response' => new Response(
                ['Accept' => 'application/json', 'Content-type' => 'application/json'],
                200,
                1024 * 8,
                0.15,
                ['status' => 'ok'],
            ),
            'errors' => [
                new Error('code', 'error', 'message', 'file', 12),
            ],
            'expectedRequest' => [
                'api_key' => 'my api key',
                'project_id' => 'my project id',
                'version' => 0.8,
                'sdk' => 'php',
                'data' => [
                    'server' => [
                        'ip' => '1.1.1.1',
                        'timezone' => 'Europe/London',
                        'software' => 'My Software',
                        'signature' => 'My Signature',
                        'protocol' => 'https',
                        'os' => [
                            'name' => 'Ubuntu',
                            'release' => '14.04',
                            'architecture' => 'x86',
                        ],
                        'encoding' => 'utf-8',
                    ],
                    'language' => [
                        'name' => 'php',
                        'version' => '8.0',
                        'expose_php' => 'Off',
                        'display_errors' => 'Off',
                    ],
                    'request' => [
                        'timestamp' => 'YYYY-MM-DD hh:mm:ss',
                        'ip' => '8.8.8.8',
                        'url' => 'http://localhost/foo',
                        'user_agent' => 'browser',
                        'method' => 'POST',
                        'headers' => [
                            'Accept' => 'application/json',
                            'Content-type' => 'application/json',
                        ],
                        'body' => [
                            'foo' => 'bar',
                        ],
                        'raw' => [
                            'baz' => 'bash',
                        ],
                    ],
                    'response' => [
                        'headers' => [
                            'Accept' => 'application/json',
                            'Content-type' => 'application/json',
                        ],
                        'code' => 200,
                        'size' => 8192,
                        'load_time' => 0.15,
                        'body' => [
                            'status' => 'ok',
                        ],
                    ],
                    'errors' => [
                        [
                            'source' => 'code',
                            'type' => 'error',
                            'message' => 'message',
                            'file' => 'file',
                            'line' => 12,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideTestData
     */
    public function test_it_correctly_serializes_request_data_on_shutdown(
        Server $server,
        Language $language,
        Request $request,
        Response $response,
        array $errors,
        array $expectedRequest
    ): void {
        $this->serverDataProvider->expects($this->once())
            ->method('getServer')
            ->willReturn($server);
        $this->languageDataProvider->expects($this->once())
            ->method('getLanguage')
            ->willReturn($language);
        $this->requestDataProvider->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);
        $this->responseDataProvider->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);
        $this->errorDataProvider->expects($this->once())
            ->method('getErrors')
            ->willReturn($errors);

        $this->mockHandler->append(new \GuzzleHttp\Psr7\Response(201));
        $this->subjectUnderTest->onShutdown();

        $this->assertCount(1, $this->container);
        $request = $this->container[0]['request'];
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Request::class, $request);

        $requestBody = $request->getBody()->getContents();
        $this->assertEquals(\Safe\json_decode($requestBody, true), $expectedRequest);

        $requestBody = \Safe\json_decode($requestBody);
        $this->validator->validate($requestBody, (object) ['$ref' => 'file://'.realpath($this->schemaPath)]);

        $this->assertTrue(
            $this->validator->isValid(),
            array_reduce(
                $this->validator->getErrors(),
                static fn (string $carry, array $error) => $carry."\n".\Safe\json_encode($error),
                ''
            )
        );
    }
}
