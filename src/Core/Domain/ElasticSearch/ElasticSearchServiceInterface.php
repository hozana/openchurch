<?php

declare(strict_types=1);

namespace App\Core\Domain\ElasticSearch;

use App\Community\Domain\Enum\CommunityIndex;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Promise\Promise;

interface ElasticSearchServiceInterface
{
    public function bulkIndex(CommunityIndex $index, array $ids, array $bodies): void;

    public function createIndex(CommunityIndex $index): Elasticsearch|Promise;

    public function deleteIndex(CommunityIndex $index): array;

    public function putMapping(CommunityIndex $index): array;

    public function searchParishIds(string $text, int $limit, int $offset): array;
}