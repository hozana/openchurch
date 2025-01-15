<?php

declare(strict_types=1);

namespace App\Field\Domain\Exception;

use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ErrorResource]
class FieldWikidataIdMissingException extends \Exception implements ProblemExceptionInterface
{
    #[Groups(['communities', 'places'])]
    public function getType(): string
    {
        return 'FieldWikidataIdMissingException';
    }

    #[Groups(['communities', 'places'])]
    public function getTitle(): ?string
    {
        return 'field wikidataId is missing';
    }

    #[Groups(['communities', 'places'])]
    public function getStatus(): ?int
    {
        return 400;
    }

    #[Groups(['communities', 'places'])]
    public function getDetail(): ?string
    {
        return sprintf('Field wikidataId is missing');
    }

    /**
     * @codeCoverageIgnore
     */
    public function getInstance(): ?string
    {
        return null;
    }
}
