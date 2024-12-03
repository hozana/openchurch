<?php

declare(strict_types=1);

namespace App\Core\Domain\Search\Service;

interface SearchServiceInterface
{
    /**
     * @return string[]
     */
    public function searchParishIds(string $text, int $limit, int $offset): array;

    /**
     * @return string[]
     */
    public function searchDioceseIds(string $text, int $limit, int $offset): array;

    /**
     * @return string[]
     */
    public function allDioceses(?int $limit = 100, ?int $offset = 0): array;

    /**
     * @return string[]
     */
    public function allParishes(?int $limit = 100, ?int $offset = 0): array;
}
