<?php

declare(strict_types=1);

namespace App\Core\Domain\Service;

interface SearchServiceInterface
{
    public function searchParishIds(string $text, int $limit, int $offset): array;

    public function searchDioceseIds(string $text, int $limit, int $offset): array;
}