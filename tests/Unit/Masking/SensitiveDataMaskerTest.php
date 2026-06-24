<?php

declare(strict_types=1);

namespace Treblle\Php\Tests\Unit\Masking;

use PHPUnit\Framework\TestCase;
use Treblle\Php\Masking\SensitiveDataMasker;

class SensitiveDataMaskerTest extends TestCase
{
    public function testMasksMatchingKeyword(): void
    {
        $masker = new SensitiveDataMasker(['password']);

        $result = $masker->maskData(['password' => 'secret123']);

        self::assertSame('*********', $result['password']);
    }

    public function testMaskingPreservesLength(): void
    {
        $masker = new SensitiveDataMasker(['token']);

        $result = $masker->maskData(['token' => 'abc']);

        self::assertSame('***', $result['token']);
    }

    public function testMaskingIsCaseInsensitive(): void
    {
        $masker = new SensitiveDataMasker(['password']);

        $result = $masker->maskData(['Password' => 'value', 'PASSWORD' => 'value2']);

        self::assertSame('*****', $result['Password']);
        self::assertSame('******', $result['PASSWORD']);
    }

    public function testDoesNotMaskNonMatchingKey(): void
    {
        $masker = new SensitiveDataMasker(['password']);

        $result = $masker->maskData(['username' => 'alice', 'email' => 'alice@example.com']);

        self::assertSame('alice', $result['username']);
        self::assertSame('alice@example.com', $result['email']);
    }

    public function testEmptyKeywordsSkipsMasking(): void
    {
        $masker = new SensitiveDataMasker([]);

        $result = $masker->maskData(['password' => 'secret', 'ssn' => '123-45-6789']);

        self::assertSame('secret', $result['password']);
        self::assertSame('123-45-6789', $result['ssn']);
    }

    public function testRecursiveMasking(): void
    {
        $masker = new SensitiveDataMasker(['password']);

        $result = $masker->maskData([
            'user' => [
                'name' => 'Alice',
                'password' => 'hunter2',
            ],
        ]);

        self::assertSame('Alice', $result['user']['name']);
        self::assertSame('*******', $result['user']['password']);
    }

    public function testMasksAuthorizationHeaderPreservingScheme(): void
    {
        $masker = new SensitiveDataMasker(['authorization']);

        $result = $masker->maskHeaders(['Authorization' => 'Bearer my-secret-token']);

        self::assertSame('Bearer ***************', $result['Authorization']);
    }

    public function testMasksBasicAuthorizationHeader(): void
    {
        $masker = new SensitiveDataMasker(['authorization']);

        $result = $masker->maskHeaders(['Authorization' => 'Basic dXNlcjpwYXNz']);

        self::assertSame('Basic ************', $result['Authorization']);
    }

    public function testMasksXApiKeyHeader(): void
    {
        $masker = new SensitiveDataMasker(['x-api-key']);

        $result = $masker->maskHeaders(['x-api-key' => 'my-key-123']);

        self::assertSame('**********', $result['x-api-key']);
    }

    public function testAuthorizationAndXApiKeyNotMaskedWhenNotInKeywords(): void
    {
        $masker = new SensitiveDataMasker([]);

        $result = $masker->maskHeaders(['Authorization' => 'Bearer token', 'x-api-key' => 'my-key']);

        self::assertSame('Bearer token', $result['Authorization']);
        self::assertSame('my-key', $result['x-api-key']);
    }

    public function testRedactsBase64Images(): void
    {
        $masker = new SensitiveDataMasker([]);

        $result = $masker->maskData(['avatar' => 'data:image/png;base64,iVBORw0KGgo=']);

        self::assertSame('[image]', $result['avatar']);
    }

    public function testMasksNumericValues(): void
    {
        $masker = new SensitiveDataMasker(['cc']);

        $result = $masker->maskData(['cc' => 1234567890123456]);

        self::assertSame('****************', $result['cc']);
    }

    public function testNonMatchingHeadersPassThrough(): void
    {
        $masker = new SensitiveDataMasker([]);

        $result = $masker->maskHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json']);

        self::assertSame('application/json', $result['Content-Type']);
        self::assertSame('application/json', $result['Accept']);
    }
}
