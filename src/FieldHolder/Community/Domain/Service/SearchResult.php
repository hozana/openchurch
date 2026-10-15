<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Domain\Service;

final readonly class SearchResult
{
    /**
     * @param list<string> $ids
     */
    public function __construct(
        public array $ids,
        public int $total,
    ) {
    }
}
