<?php

declare(strict_types=1);

namespace App\Tests\Shared\Unit\Domain;

use App\Shared\Domain\Cast;
use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;

final class CastTest extends TestCase
{
    /**
     * @dataProvider toStringDataProvider
     */
    public function testToString(string $expected, mixed $value): void
    {
        self::assertSame($expected, Cast::toString($value));
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function toStringDataProvider(): iterable
    {
        yield 'string is returned as is' => ['openchurch', 'openchurch'];
        yield 'empty string stays empty' => ['', ''];
        yield 'int is rendered' => ['42', 42];
        yield 'negative int is rendered' => ['-7', -7];
        yield 'float is rendered' => ['1.5', 1.5];
        yield 'true becomes 1' => ['1', true];
        yield 'false becomes empty' => ['', false];
        yield 'null becomes empty' => ['', null];
        yield 'Stringable is rendered' => ['from stringable', new class implements Stringable {
            public function __toString(): string
            {
                return 'from stringable';
            }
        }];

        // Values without a string representation must never raise, they collapse to ''.
        yield 'array collapses to empty' => ['', ['a', 'b']];
        yield 'empty array collapses to empty' => ['', []];
        yield 'plain object collapses to empty' => ['', new stdClass()];
    }

    /**
     * @dataProvider toNullableStringDataProvider
     */
    public function testToNullableString(?string $expected, mixed $value): void
    {
        self::assertSame($expected, Cast::toNullableString($value));
    }

    /**
     * @return iterable<string, array{string|null, mixed}>
     */
    public static function toNullableStringDataProvider(): iterable
    {
        yield 'null stays null' => [null, null];
        yield 'empty string stays a string' => ['', ''];
        yield 'string is returned as is' => ['openchurch', 'openchurch'];
        yield 'int is rendered' => ['42', 42];
        yield 'false is not confused with null' => ['', false];
        yield 'array collapses to empty string' => ['', ['a']];
    }

    /**
     * toNullableString is the only way to tell "absent" from "empty".
     */
    public function testToNullableStringDistinguishesNullFromEmptyString(): void
    {
        self::assertNull(Cast::toNullableString(null));
        self::assertSame('', Cast::toNullableString(''));
        self::assertSame('', Cast::toString(null));
    }

    /**
     * @dataProvider toIntDataProvider
     */
    public function testToInt(int $expected, mixed $value): void
    {
        self::assertSame($expected, Cast::toInt($value));
    }

    /**
     * @return iterable<string, array{int, mixed}>
     */
    public static function toIntDataProvider(): iterable
    {
        yield 'int is returned as is' => [42, 42];
        yield 'zero is returned as is' => [0, 0];
        yield 'negative int is returned as is' => [-7, -7];
        yield 'numeric string is converted' => [42, '42'];
        yield 'negative numeric string is converted' => [-7, '-7'];
        yield 'numeric string with float is truncated' => [1, '1.9'];
        yield 'float is truncated' => [1, 1.9];
        yield 'numeric string with spaces is converted' => [42, ' 42'];

        // Anything not numeric falls back to 0 rather than raising.
        yield 'non numeric string falls back to zero' => [0, 'abc'];
        yield 'empty string falls back to zero' => [0, ''];
        yield 'null falls back to zero' => [0, null];
        yield 'true falls back to zero' => [0, true];
        yield 'array falls back to zero' => [0, [1, 2]];
        yield 'object falls back to zero' => [0, new stdClass()];
    }
}
