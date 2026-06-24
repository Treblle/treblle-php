<?php

declare(strict_types=1);

namespace Treblle\Php\Tests\Unit\Filter;

use PHPUnit\Framework\TestCase;
use Treblle\Php\Filter\RequestTypeFilter;

class RequestTypeFilterTest extends TestCase
{
    private RequestTypeFilter $filter;

    protected function setUp(): void
    {
        $this->filter = new RequestTypeFilter();
    }

    public function testSkipsStaticFileExtensions(): void
    {
        self::assertTrue($this->filter->shouldSkip('/style.css'));
        self::assertTrue($this->filter->shouldSkip('/app.js'));
        self::assertTrue($this->filter->shouldSkip('/index.html'));
        self::assertTrue($this->filter->shouldSkip('/favicon.ico'));
        self::assertTrue($this->filter->shouldSkip('/logo.png'));
        self::assertTrue($this->filter->shouldSkip('/font.woff2'));
        self::assertTrue($this->filter->shouldSkip('/.env'));
    }

    public function testSkipsFileExtensionCaseInsensitive(): void
    {
        self::assertTrue($this->filter->shouldSkip('/style.CSS'));
        self::assertTrue($this->filter->shouldSkip('/image.PNG'));
    }

    public function testDoesNotSkipJsonApiRoutes(): void
    {
        self::assertFalse($this->filter->shouldSkip('/api/users', 'application/json'));
        self::assertFalse($this->filter->shouldSkip('/api/v1/posts', 'application/json; charset=utf-8'));
    }

    public function testSkipsNonJsonContentType(): void
    {
        self::assertTrue($this->filter->shouldSkip('/api/page', 'text/html'));
        self::assertTrue($this->filter->shouldSkip('/api/data', 'text/plain'));
        self::assertTrue($this->filter->shouldSkip('/api/style', 'text/css'));
    }

    public function testAllowsRequestWithoutContentTypeYet(): void
    {
        // No content type means we haven't seen the response yet — don't skip
        self::assertFalse($this->filter->shouldSkip('/api/users', null));
    }

    public function testSkipsEnvFile(): void
    {
        self::assertTrue($this->filter->shouldSkip('/.env'));
        self::assertTrue($this->filter->shouldSkip('/config/.env'));
    }

    public function testDoesNotSkipApiPathWithNoExtension(): void
    {
        self::assertFalse($this->filter->shouldSkip('/api/v1/health'));
        self::assertFalse($this->filter->shouldSkip('/users/123'));
    }
}
