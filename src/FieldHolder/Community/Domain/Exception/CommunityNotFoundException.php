<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ErrorResource]
class CommunityNotFoundException extends Exception implements ProblemExceptionInterface
{
    public function __construct(
        private Uuid $communityId,
    ) {
    }

    #[Groups(['communities'])]
    public function getType(): string
    {
        return 'CommunityNotFoundException';
    }

    #[Groups(['communities'])]
    public function getTitle(): ?string
    {
        return 'community not found';
    }

    public function getStatus(): ?int
    {
        return 404;
    }

    #[Groups(['communities'])]
    public function getDetail(): ?string
    {
        return sprintf('Community with id %s not found', $this->communityId);
    }

    /**
     * @codeCoverageIgnore
     */
    public function getInstance(): ?string
    {
        return null;
    }
}
