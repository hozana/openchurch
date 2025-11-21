<?php

declare(strict_types=1);

namespace App\Field\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;

#[ErrorResource]
class FieldEntityNotFoundException extends Exception implements ProblemExceptionInterface
{
    public function __construct(
        private readonly mixed $value,
    ) {
    }

    #[Groups(['communities', 'places'])]
    public function getType(): string
    {
        return 'FieldEntityNotFoundException';
    }

    #[Groups(['communities', 'places'])]
    public function getTitle(): ?string
    {
        return 'entity not found from provided id(s)';
    }

    #[Groups(['communities', 'places'])]
    public function getStatus(): ?int
    {
        return 400;
    }

    #[Groups(['communities', 'places'])]
    public function getDetail(): ?string
    {
        return sprintf('%s": Could not find some values from provided ID(s)', rtrim(
            array_reduce(
                (array) $this->value,
                fn ($value, $prev) => "$prev, $value",
                ''
            ),
            ','
        ));
    }

    /**
     * @codeCoverageIgnore
     */
    public function getInstance(): ?string
    {
        return null;
    }
}
