<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Domain\Service;

use App\Shared\Domain\Enum\SearchIndex;

interface SearchHelperInterface
{
    /**
     * @param array<mixed> $ids
     * @param array<mixed> $bodies
     */
    public function bulkIndex(SearchIndex $index, array $ids, array $bodies): void;

    /**
     * Indexes (replacing them) and deletes documents, in a single request.
     *
     * @param list<array{index: SearchIndex, id: string, body: array<string, mixed>}> $documents
     * @param list<array{index: SearchIndex, id: string}>                             $deletions documents which may not exist
     */
    public function bulkWrite(array $documents, array $deletions = []): void;

    public function createIndex(SearchIndex $index): mixed;

    /**
     * @param array<mixed> $body
     *
     * @return array<mixed>
     */
    public function upsertElement(SearchIndex $index, string $id, array $body): mixed;

    public function existDocument(SearchIndex $index, string $id): bool;

    /**
     * @return array<mixed>|null
     */
    public function getDocument(SearchIndex $index, string $id): ?array;

    /**
     * Removes the document if it exists.
     */
    public function deleteDocument(SearchIndex $index, string $id): void;

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
