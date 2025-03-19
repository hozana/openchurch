<?php

namespace App\FieldHolder\Application;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Place\Domain\Model\Place;
use App\FieldHolder\Place\Domain\Repository\PlaceRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

final class FieldHolderUpsertService
{
    public function __construct(
        private readonly EntityManagerInterface $fieldRepo,
        private readonly CommunityRepositoryInterface $communityRepo,
        private readonly PlaceRepositoryInterface $placeRepo,
    ) {
    }

    /**
     * @param Field[] $fields
     */
    public function getFieldByName(array $fields, string $fieldName): ?Field
    {
        return array_find($fields, fn (Field $field) => $field->name === $fieldName);
    }

    public function handleError(Community|Place $entity, ProblemExceptionInterface|ValidationException $e): string
    {
        foreach ($entity->fields as $field) {
            $this->fieldRepo->detach($field);
        }

        match ($entity::class) {
            Community::class => $this->communityRepo->detach($entity),
            Place::class => $this->placeRepo->detach($entity),
            default => throw new RuntimeException(sprintf('Unknown entity class %s', $entity::class)),
        };

        if ($e instanceof ValidationException) {
            return $e->getMessage();
        }

        return $e->getDetail();
    }
}
