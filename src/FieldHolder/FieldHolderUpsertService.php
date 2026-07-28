<?php

namespace App\FieldHolder;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Place\Domain\Model\Place;
use App\FieldHolder\Place\Domain\Repository\PlaceRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class FieldHolderUpsertService
{
    public function __construct(
        private EntityManagerInterface $fieldRepo,
        private CommunityRepositoryInterface $communityRepo,
        private PlaceRepositoryInterface $placeRepo,
        private DenormalizerInterface $denormalizer,
    ) {
    }

    /**
     * API Platform's input denormalization does not recurse into the `array<Field[]>` collection,
     * so the nested entries arrive as raw arrays. Rebuild them as the Field objects expected below.
     *
     * @param array<mixed> $wikidataEntities
     *
     * @return Field[][]
     */
    public function toFieldEntities(array $wikidataEntities): array
    {
        /** @var Field[][] $entities */
        $entities = $this->denormalizer->denormalize($wikidataEntities, Field::class.'[][]');

        return $entities;
    }

    /**
     * @param Field[] $fields
     */
    public function getFieldByName(array $fields, string $fieldName): ?Field
    {
        return array_find($fields, static fn (Field $field) => $field->name === $fieldName);
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

        return $e->getDetail() ?? '';
    }
}
