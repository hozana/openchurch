<?php

declare(strict_types=1);

namespace App\Place\Domain\Exception;

use Symfony\Component\Uid\Uuid;
use ApiPlatform\Metadata\Exception\HttpExceptionInterface;

class PlaceNotFoundException implements HttpExceptionInterface
{
    public function getStatusCode(): int
    {
        return 404;
    }

    public function getHeaders(): array
    {
        return [];
    }

    // public function __construct(Uuid $id, int $code = 0, ?\Throwable $previous = null)
    // {
    //     parent::__construct(sprintf('Cannot find place with id %s', $id->toString()), $code, $previous);
    // }
}