<?php

declare(strict_types=1);

namespace App\Community\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Symfony\Component\Uid\Uuid;

#[ErrorResource(
    normalizationContext: ['groups' => null],
    status: 404,
)]
class CommunityNotFoundException extends \Exception implements ProblemExceptionInterface
{
    public function __construct(
        private Uuid $communityId,
    )
    {}

    public function getType(): string
    {
        return 'PlaceNotFoundException';
    }

    public function getTitle(): ?string
    {
        return "place not found";
    }

    public function getStatus(): ?int
    {
        return 404;
    }

    public function getDetail(): ?string
    {
        return sprintf('Community with id %s not found', $this->communityId);
    }

    public function getInstance(): ?string
    {
        return null;
    }
}