<?php

declare(strict_types=1);

namespace App\FieldHolder\Place\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ErrorResource]
class PlaceNotFoundException extends Exception implements ProblemExceptionInterface
{
    public function __construct(
        private Uuid $placeId,
    ) {
    }

    #[Groups(['places'])]
    public function getType(): string
    {
        return 'PlaceNotFoundException';
    }

    #[Groups(['places'])]
    public function getTitle(): ?string
    {
        return 'place not found';
    }

    #[Groups(['places'])]
    public function getStatus(): ?int
    {
        return 404;
    }

    #[Groups(['places'])]
    public function getDetail(): ?string
    {
        return sprintf('Place with id %s not found', $this->placeId);
    }

    /**
     * @codeCoverageIgnore
     */
    public function getInstance(): ?string
    {
        return null;
    }
}
