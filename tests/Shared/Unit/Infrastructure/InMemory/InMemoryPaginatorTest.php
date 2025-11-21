<?php

declare(strict_types=1);

namespace App\Tests\Shared\Unit\Infrastructure\InMemory;

use App\Shared\Infrastructure\InMemory\InMemoryPaginator;
use ArrayIterator;
use PHPUnit\Framework\TestCase;

final class InMemoryPaginatorTest extends TestCase
{
    /**
     * @dataProvider getLastPageDataProvider
     */
    public function testGetLastPage(int $lastPage, int $itemsPerPage): void
    {
        $items = [1, 2, 3];

        $paginator = new InMemoryPaginator(
            items: new ArrayIterator($items),
            totalItems: count($items),
            currentPage: 1,
            itemsPerPage: $itemsPerPage,
        );

        self::assertSame($lastPage, $paginator->getLastPage());
    }

    /**
     * @return iterable<array{int, int}>
     */
    public static function getLastPageDataProvider(): iterable
    {
        yield [3, 1];
        yield [2, 2];
        yield [1, 3];
    }

    /**
     * @dataProvider iteratorDataProvider
     *
     * @param array<int, object> $page
     */
    public function testIterator(int $currentPage, int $itemsPerPage, array $page): void
    {
        $items = [1, 2, 3];

        $paginator = new InMemoryPaginator(
            items: new ArrayIterator($items),
            totalItems: count($items),
            currentPage: $currentPage,
            itemsPerPage: $itemsPerPage,
        );

        self::assertCount(count($page), $paginator);

        $i = 0;
        foreach ($paginator as $item) {
            self::assertSame($page[$i], $item);
            ++$i;
        }
    }

    /**
     * @return iterable<array{int, int, array<int>}>
     */
    public static function iteratorDataProvider(): iterable
    {
        yield [1, 3, [1, 2, 3]];
        yield [2, 3, []];
        yield [2, 2, [3]];
        yield [1, 1, [1]];
        yield [2, 1, [2]];
        yield [3, 1, [3]];
        yield [4, 1, []];
    }
}
