<?php

declare(strict_types=1);

namespace App\Core\Domain\Search\Helper;

use App\Shared\Domain\Enum\SearchIndex;

interface SearchHelperInterface
{
    public function bulkIndex(SearchIndex $index, array $ids, array $bodies): void;

    public function createIndex(SearchIndex $index): mixed;

    public function deleteIndex(SearchIndex $index): array;

    public function putMapping(SearchIndex $index): array;

    public function search(SearchIndex $index, array $body = []): array;

    public function all(SearchIndex $index, int $offset, int $limit): array;

    public function refresh(SearchIndex $index): void;
}