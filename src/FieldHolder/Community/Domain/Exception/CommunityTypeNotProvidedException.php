<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ErrorResource]
class CommunityTypeNotProvidedException extends \Exception implements ProblemExceptionInterface
{
    #[Groups(['communities'])]
    public function getType(): string
    {
        return 'CommunityTypeNotProvidedException';
    }

    #[Groups(['communities'])]
    public function getTitle(): ?string
    {
        return 'community type not provided';
    }

    public function getStatus(): ?int
    {
        return 400;
    }

    #[Groups(['communities'])]
    public function getDetail(): ?string
    {
        return sprintf('You must provide a community type when filtering by name');
    }

    /**
     * @codeCoverageIgnore
     */
    public function getInstance(): ?string
    {
        return null;
    }
}
