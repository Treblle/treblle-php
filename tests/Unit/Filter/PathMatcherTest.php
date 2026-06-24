<?php

declare(strict_types=1);

namespace Treblle\Php\Tests\Unit\Filter;

use PHPUnit\Framework\TestCase;
use Treblle\Php\Filter\PathMatcher;

class PathMatcherTest extends TestCase
{
    public function testExactMatch(): void
    {
        $matcher = new PathMatcher(['/health', '/uptime', '/status']);

        self::assertTrue($matcher->matches('/health'));
        self::assertTrue($matcher->matches('/status'));
        self::assertFalse($matcher->matches('/api/users'));
        self::assertFalse($matcher->matches('/health-check'));
    }

    public function testExactMatchIsCaseInsensitive(): void
    {
        $matcher = new PathMatcher(['/Health']);

        self::assertTrue($matcher->matches('/health'));
        self::assertTrue($matcher->matches('/HEALTH'));
    }

    public function testWildcardMatch(): void
    {
        $matcher = new PathMatcher(['admin/*']);

        self::assertTrue($matcher->matches('admin/users'));
        self::assertTrue($matcher->matches('admin/settings/edit'));
        self::assertFalse($matcher->matches('/api/users'));
    }

    public function testWildcardWithLeadingSlash(): void
    {
        $matcher = new PathMatcher(['/admin/*']);

        self::assertTrue($matcher->matches('/admin/dashboard'));
        self::assertTrue($matcher->matches('/admin/users/123'));
        self::assertFalse($matcher->matches('/user/admin'));
    }

    public function testSingleCharacterWildcard(): void
    {
        $matcher = new PathMatcher(['/api/v?/users']);

        self::assertTrue($matcher->matches('/api/v1/users'));
        self::assertTrue($matcher->matches('/api/v2/users'));
        self::assertFalse($matcher->matches('/api/v10/users'));
    }

    public function testRegexMatch(): void
    {
        $matcher = new PathMatcher(['/^\/api\/v\d+\/.*$/']);

        self::assertTrue($matcher->matches('/api/v1/users'));
        self::assertTrue($matcher->matches('/api/v2/posts/123'));
        self::assertFalse($matcher->matches('/web/users'));
    }

    public function testEmptyExcludedPaths(): void
    {
        $matcher = new PathMatcher([]);

        self::assertFalse($matcher->matches('/health'));
        self::assertFalse($matcher->matches('/anything'));
    }

    public function testNoMatchReturnsTrue(): void
    {
        $matcher = new PathMatcher(['/health', '/status']);

        self::assertFalse($matcher->matches('/api/users'));
    }

    public function testMultiplePatterns(): void
    {
        $matcher = new PathMatcher(['/health', 'admin/*', '/^\/debug\//']);

        self::assertTrue($matcher->matches('/health'));
        self::assertTrue($matcher->matches('admin/panel'));
        self::assertTrue($matcher->matches('/debug/info'));
        self::assertFalse($matcher->matches('/api/data'));
    }
}
