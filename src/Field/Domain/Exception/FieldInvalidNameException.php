<?php

declare(strict_types=1);

namespace App\Field\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ErrorResource]
class FieldInvalidNameException extends \Exception implements ProblemExceptionInterface
{
    public function __construct(
        private readonly string $name,
    )
    {}

    #[Groups(['communities', 'places'])]
    public function getType(): string
    {
        return 'FieldInvalidNameException';
    }

    #[Groups(['communities', 'places'])]
    public function getTitle(): ?string
    {
        return "invalid field name";
    }

    #[Groups(['communities', 'places'])]
    public function getStatus(): ?int
    {
        return 400;
    }

    #[Groups(['communities', 'places'])]
    public function getDetail(): ?string
    {
        return sprintf('Field %s: invalid field name', $this->name);
    }

    public function getInstance(): ?string
    {
        return null;
    }
}