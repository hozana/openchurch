<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Domain\Service;

use App\FieldHolder\Community\Domain\Model\Community;

interface SearchServiceInterface
{
    /**
     * @return string[]
     */
    public function searchParishIds(string $text, ?string $dioceseId, int $limit, int $offset): array;

    public function findParish(string $id): ?Community;

    /**
     * @return string[]
     */
    public function searchDioceseIds(string $text, int $limit, int $offset): array;

    public function findDiocese(string $text): ?Community;

    /**
     * @return string[]
     */
    public function allDioceses(?int $limit = 100, ?int $offset = 0): array;

    /**
     * @return string[]
     */
    public function allParishes(?int $limit = 100, ?int $offset = 0): array;
}
