<?php

declare(strict_types=1);

namespace App\Field\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ErrorResource]
class FieldUnicityViolationException extends \Exception implements ProblemExceptionInterface
{
    public function __construct(
        private readonly string $name,
        private readonly mixed $value,
    )
    {}

    #[Groups(['communities', 'places'])]
    public function getType(): string
    {
        return 'FieldUnicityViolationException';
    }

    #[Groups(['communities', 'places'])]
    public function getTitle(): ?string
    {
        return "field unicity violation";
    }

    #[Groups(['communities', 'places'])]
    public function getStatus(): ?int
    {
        return 400;
    }

    #[Groups(['communities', 'places'])]
    public function getDetail(): ?string
    {
        return sprintf('Found duplicate for field %s with value %s', $this->name, $this->value);
    }

    /**
     * @codeCoverageIgnore
     */
    public function getInstance(): ?string
    {
        return null;
    }
}