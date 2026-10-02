<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Infrastructure\ElasticSearch;

use Elastic\Elasticsearch\Exception\ElasticsearchException;
use RuntimeException;

final class BulkIndexException extends RuntimeException implements ElasticsearchException
{
    /**
     * @param array<mixed> $response
     */
    public static function fromResponse(array $response): self
    {
        $failures = [];
        foreach (is_array($response['items'] ?? null) ? $response['items'] : [] as $item) {
            // Each item holds a single operation, keyed by its type (index, delete...)
            $operation = is_array($item) ? reset($item) : null;
            $error = is_array($operation) ? $operation['error'] ?? null : null;
            if (is_array($operation) && is_array($error)) {
                $index = is_string($operation['_index'] ?? null) ? $operation['_index'] : '?';
                $id = is_string($operation['_id'] ?? null) ? $operation['_id'] : '?';
                $reason = is_string($error['reason'] ?? null) ? $error['reason'] : 'unknown reason';
                $failures[] = "{$index}/{$id}: {$reason}";
            }
        }

        return new self(sprintf('%d operation(s) failed: %s', count($failures), implode('; ', array_slice($failures, 0, 5))));
    }
}
