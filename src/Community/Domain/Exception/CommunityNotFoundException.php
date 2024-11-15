<?php

declare(strict_types=1);

namespace App\Community\Domain\Exception;

use ApiPlatform\Metadata\Exception\HttpExceptionInterface;

class CommunityNotFoundException implements HttpExceptionInterface
{
    public function getStatusCode(): int
    {
        return 404;
    }

    public function getHeaders(): array
    {
        return [];
    }
}