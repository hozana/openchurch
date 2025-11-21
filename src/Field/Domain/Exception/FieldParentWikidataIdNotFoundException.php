<?php

declare(strict_types=1);

namespace App\Field\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Exception;
use Symfony\Component\Serializer\Annotation\Groups;

#[ErrorResource]
class FieldParentWikidataIdNotFoundException extends Exception implements ProblemExceptionInterface
{
    /**
     * @param int[] $parentWikidataIds
     */
    public function __construct(
        private readonly array $parentWikidataIds,
    ) {
    }

    #[Groups(['communities', 'places'])]
    public function getType(): string
    {
        return 'FieldParentWikidataIdNotFoundException';
    }

    #[Groups(['communities', 'places'])]
    public function getTitle(): ?string
    {
        return 'parentWikidataId not found';
    }

    #[Groups(['communities', 'places'])]
    public function getStatus(): ?int
    {
        return 404;
    }

    #[Groups(['communities', 'places'])]
    public function getDetail(): ?string
    {
        return sprintf('Field parentWikidataId %s not found', implode(', ', $this->parentWikidataIds));
    }

    /**
     * @codeCoverageIgnore
     */
    public function getInstance(): ?string
    {
        return null;
    }
}
