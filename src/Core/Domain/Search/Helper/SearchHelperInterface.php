<?php

declare(strict_types=1);

namespace App\Core\Domain\Search\Helper;

use App\Shared\Domain\Enum\SearchIndex;

interface SearchHelperInterface
{
    /**
     * @param array<mixed> $ids
     * @param array<mixed> $bodies
     */
    public function bulkIndex(SearchIndex $index, array $ids, array $bodies): void;

    public function createIndex(SearchIndex $index): mixed;

    /**
     * @return array<mixed>
     */
    public function deleteIndex(SearchIndex $index): array;

    /**
     * @return array<mixed>
     */
    public function putMapping(SearchIndex $index): array;

    /**
     * @param array<mixed> $body
     *
     * @return array<mixed>
     */
    public function search(SearchIndex $index, array $body = []): array;

    /**
     * @return array<mixed>
     */
    public function all(SearchIndex $index, int $offset, int $limit): array;

    public function refresh(SearchIndex $index): void;
}
