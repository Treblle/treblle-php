<?php

declare(strict_types=1);

namespace Tests\Treblle\Unit;

use PHPUnit\Framework\TestCase;
use Treblle\PhpHelper;

/**
 * @internal
 * @coversNothing
 *
 * @small
 */
final class PhpHelperTest extends TestCase
{
    public function test_it_fetches_string_value(): void
    {
        $helper = new PhpHelper();
        \Safe\ini_set('date.timezone', 'Europe/London');
        $value = $helper->getIniValue('date.timezone');
        $this->assertSame('Europe/London', $value);
    }

    public function test_it_fetches_numeric_value(): void
    {
        $helper = new PhpHelper();
        \Safe\ini_set('date.default_latitude', '31.7667');
        $value = $helper->getIniValue('date.default_latitude');
        $this->assertSame('31.7667', $value);
    }

    public function test_it_fetches_empty_string_value_as_bool(): void
    {
        $helper = new PhpHelper();
        \Safe\ini_set('assert.callback', '');
        $value = $helper->getIniValue('assert.callback');
        $this->assertSame('Off', $value);
    }

    public function test_it_fetches_bool_value(): void
    {
        $helper = new PhpHelper();
        \Safe\ini_set('display_errors', '1');
        $value = $helper->getIniValue('display_errors');
        $this->assertSame('On', $value);

        \Safe\ini_set('display_errors', '0');
        $value = $helper->getIniValue('display_errors');
        $this->assertSame('Off', $value);
    }

    public function test_it_handles_invalid_keys(): void
    {
        $helper = new PhpHelper();
        $value = $helper->getIniValue('non_existing');
        $this->assertSame('<unknown>', $value);
    }
}
