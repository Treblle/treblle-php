<?php

declare(strict_types=1);

namespace Tests\Treblle\Unit;

use PHPUnit\Framework\TestCase;
use Treblle\InMemoryErrorDataProvider;
use Treblle\Model\Error;

/**
 * @internal
 * @coversNothing
 *
 * @small
 */
final class InMemoryErrorDataProviderTest extends TestCase
{
    public function test_it_collects_errors(): void
    {
        $errors = new InMemoryErrorDataProvider();
        $this->assertSame([], $errors->getErrors());

        $error = new Error('source', 'type', 'message', 'file', 1);
        $errors->addError($error);
        $this->assertSame([$error], $errors->getErrors());

        $error2 = new Error('source2', 'type2', 'message2', 'file2', 1);
        $errors->addError($error2);
        $this->assertSame([$error, $error2], $errors->getErrors());
    }
}
