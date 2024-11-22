<?php

declare(strict_types=1);

namespace App\Core\Domain\Helper;

use App\Shared\Domain\Enum\SearchIndex;

interface ElasticSearchHelperInterface
{
    public function bulkIndex(SearchIndex $index, array $ids, array $bodies): void;

    public function createIndex(SearchIndex $index): mixed;

    public function deleteIndex(SearchIndex $index): array;

    public function putMapping(SearchIndex $index): array;

    public function search(SearchIndex $index, array $body = []): array;

    public function refresh(SearchIndex $index): void;
}