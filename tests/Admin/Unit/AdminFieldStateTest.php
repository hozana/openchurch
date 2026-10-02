<?php

declare(strict_types=1);

namespace App\Tests\Admin\Unit;

use App\Admin\Application\AdminFieldState;
use PHPUnit\Framework\TestCase;

final class AdminFieldStateTest extends TestCase
{
    public function testEquality(): void
    {
        self::assertTrue(new AdminFieldState(['a', 'b'], null)->equals(new AdminFieldState(['b', 'a'], null)), 'The order of related ids does not matter');
        self::assertFalse(new AdminFieldState('a', null)->equals(new AdminFieldState('a', 'why')));
        self::assertFalse(new AdminFieldState('1', null)->equals(new AdminFieldState(1, null)));
        self::assertFalse(new AdminFieldState(null, null)->equals(new AdminFieldState('a', null)));
    }
}
