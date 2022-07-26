<?php

declare(strict_types=1);

namespace Tests\Treblle\Unit;

use Assert\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Treblle\PayloadAnonymizer;

/**
 * @internal
 * @coversNothing
 *
 * @small
 */
final class PayloadAnnonymizerTest extends TestCase
{
    /**
     * @param list<string> $fields
     * @param array<string, mixed> $data
     * @param array<string, mixed> $expected
     * @dataProvider provideMaskingTestData
     */
    public function test_it_masks_fields_passed_as_argument(array $fields, array $data, array $expected): void
    {
        $anonymizer = new PayloadAnonymizer($fields);
        $masked = $anonymizer->annonymize($data);
        $this->assertSame($expected, $masked);
    }

    /**
     * @return iterable<string, mixed>
     */
    public function provideMaskingTestData(): iterable
    {
        $fields = [
            'password',
            'pwd',
            'secret',
            'password_confirmation',
            'cc',
            'card_number',
            'ccv',
            'ssn',
            'credit_score',
        ];

        yield 'single field masking' => [
            $fields,
            [
                'credit_score' => '1231231',
                'foo' => 'bar',
            ],
            [
                'credit_score' => '*******',
                'foo' => 'bar',
            ],
        ];

        yield 'nested array masking' => [
            $fields,
            [
                'data' => [
                    'pwd' => 'pwd',
                    'secret' => 'secret',
                ],
                'password' => [
                    'password' => 'password',
                    'password_confirmation' => 'password_confirmation',
                ],
                'cc' => 'cc',
                'card_number' => 'card_number',
                'ccv' => 'ccv',
                'ssn' => 'ssn',
                'credit_score' => 'credit_score',
            ],
            [
                'data' => [
                    'pwd' => '***',
                    'secret' => '******',
                ],
                'password' => [
                    'password' => '********',
                    'password_confirmation' => '*********************',
                ],
                'cc' => '**',
                'card_number' => '***********',
                'ccv' => '***',
                'ssn' => '***',
                'credit_score' => '************',
            ],
        ];

        yield 'not masking non-numeric values' => [
            $fields,
            [
                'credit_score' => 1_231_231,
                'pwd' => 11.11,
            ],
            [
                'credit_score' => 1_231_231,
                'pwd' => 11.11,
            ],
        ];

        $dt = new \DateTimeImmutable();
        $object = new \stdClass();

        yield 'not breaking on objects' => [
            $fields,
            [
                'credit_score' => $dt,
                'pwd' => $object,
            ],
            [
                'credit_score' => $dt,
                'pwd' => $object,
            ],
        ];
    }

    public function test_it_throws_given_non_string_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PayloadAnonymizer([1, 2, 3]);
    }

    public function test_it_throws_given_non_list(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PayloadAnonymizer([1, 2, 3]);
    }
}
