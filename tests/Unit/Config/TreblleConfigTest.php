<?php

declare(strict_types=1);

namespace Treblle\Php\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use Treblle\Php\Config\TreblleConfig;

class TreblleConfigTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $config = new TreblleConfig('sdk-token', 'api-key');

        self::assertSame('sdk-token', $config->sdkToken);
        self::assertSame('api-key', $config->apiKey);
        self::assertFalse($config->debug);
        self::assertSame(TreblleConfig::DEFAULT_MASKED_KEYWORDS, $config->maskedKeywords);
        self::assertSame([], $config->excludedPaths);
        self::assertNull($config->customIngress);
        self::assertTrue($config->enabled);
    }

    public function testDefaultIngressUrl(): void
    {
        $config = new TreblleConfig('sdk-token', 'api-key');

        self::assertSame('https://ingress.treblle.com', $config->getIngressUrl());
    }

    public function testCustomIngressUrl(): void
    {
        $config = new TreblleConfig('sdk-token', 'api-key', customIngress: 'https://ingress-eu.treblle.com');

        self::assertSame('https://ingress-eu.treblle.com', $config->getIngressUrl());
    }

    public function testCustomMaskedKeywords(): void
    {
        $keywords = ['token', 'secret_key'];
        $config = new TreblleConfig('sdk-token', 'api-key', maskedKeywords: $keywords);

        self::assertSame($keywords, $config->maskedKeywords);
    }

    public function testDefaultMaskedKeywordsContainsPassword(): void
    {
        self::assertContains('password', TreblleConfig::DEFAULT_MASKED_KEYWORDS);
        self::assertContains('ssn', TreblleConfig::DEFAULT_MASKED_KEYWORDS);
        self::assertContains('credit_score', TreblleConfig::DEFAULT_MASKED_KEYWORDS);
    }

    public function testSdkConstants(): void
    {
        self::assertSame('php', TreblleConfig::SDK_NAME);
        self::assertSame(60, TreblleConfig::SDK_VERSION);
    }

    public function testDisabled(): void
    {
        $config = new TreblleConfig('sdk-token', 'api-key', enabled: false);

        self::assertFalse($config->enabled);
    }
}
