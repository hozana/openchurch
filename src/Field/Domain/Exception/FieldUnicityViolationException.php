<?php

declare(strict_types=1);

namespace App\Field\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\HttpExceptionInterface;

#[ErrorResource]
class FieldUnicityViolationException extends \Exception implements HttpExceptionInterface
{
    public function __construct(
        private readonly string $name,
        private readonly mixed $value,
    )
    {
        
    }

    public function getStatusCode(): int
    {
        return 455;
    }
    
    public function getHeaders(): array
    {
        return [];
    }

    public function getDetail(): ?string
    {
        $this->message = 'irijgregijr';
        return 'Found duplicate for field %s with value %s';
    }
}