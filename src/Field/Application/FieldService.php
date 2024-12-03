<?php

namespace App\Field\Application;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Agent\Domain\Model\Agent;
use App\Community\Domain\Model\Community;
use App\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldPlace;
use App\Field\Domain\Exception\FieldEntityNotFoundException;
use App\Field\Domain\Exception\FieldInvalidNameException;
use App\Field\Domain\Exception\FieldUnicityViolationException;
use App\Field\Domain\Model\Field;
use App\Field\Domain\Repository\FieldRepositoryInterface;
use App\Place\Domain\Model\Place;
use App\Place\Domain\Repository\PlaceRepositoryInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function Symfony\Component\String\s;

final class FieldService
{
    public function __construct(
        private readonly CommunityRepositoryInterface $communityRepository,
        private readonly PlaceRepositoryInterface $placeRepository,
        private readonly FieldRepositoryInterface $fieldRepo,
        private ValidatorInterface $validator,
        private Security $security,
    ) {
    }

    /**
     * @param Field[] $fieldPayloads
     *
     * @return Collection<int, Field>
     */
    public function upsertFields(Place|Community $entity, array $fieldPayloads): Collection
    {
        /** @var Collection<int, Field> $insertedFields */
        $insertedFields = new ArrayCollection();

        /** @var Agent $agent */
        $agent = $this->security->getUser();

        foreach ($fieldPayloads as $fieldPayload) {
            $enumValue = match ($entity::class) {
                Place::class => FieldPlace::tryFrom($fieldPayload->name),
                Community::class => FieldCommunity::tryFrom($fieldPayload->name),
                default => null,
            };
            if (null === $enumValue) {
                throw new FieldInvalidNameException($fieldPayload->name);
            }

            $field = $this->getOrCreate(
                $entity,
                $enumValue,
                $agent,
            );
            $value = $this->maybeTransformEntities($enumValue, $fieldPayload->value);

            if (Community::class === $entity::class) {
                $field->community = $entity;
            } else {
                $field->place = $entity;
            }
            $field->name = $fieldPayload->name;
            $field->value = $value;
            $field->engine = $fieldPayload->engine;
            $field->reliability = $fieldPayload->reliability;
            $field->source = $fieldPayload->source;
            $field->explanation = $fieldPayload->explanation;
            $field->touch();

            // Unique constraints validation (TODO use custom Assert instead)
            if (null !== $field->value
                && in_array($field->name, Field::UNIQUE_CONSTRAINTS, true)
                && (null !== $attachedToId = $this->fieldRepo->exists($entity->id, $enumValue, $field->value))
                && $attachedToId !== $entity->id->toString()
            ) {
                throw new FieldUnicityViolationException($field->name, $field->value);
            }

            $violations = $this->validator->validate($field);
            if (count($violations) > 0) {
                throw new ValidationException($violations);
            }

            $field->applyValue(); // Dynamycally set the value to the correct property (intVal, stringVal, ...)

            $this->fieldRepo->add($field);
            $insertedFields[] = $field;
        }

        return $insertedFields;
    }

    private function getOrCreate(Place|Community $entity, FieldPlace|FieldCommunity $nameEnum, Agent $agent): Field
    {
        $field = $entity->getFieldByNameAndAgent($nameEnum, $agent);
        if (!$field) {
            $field = new Field();
            $field->agent = $agent;
            $this->fieldRepo->add($field);
        }

        return $field;
    }

    /**
     * @return Community|Community[]|Place|Place[]|null
     */
    private function maybeTransformEntities(FieldCommunity|FieldPlace $nameEnum, mixed $value): mixed
    {
        $type = $nameEnum->getType();
        if (!in_array($type, [
            'Community',
            'Community[]',
            'Place',
            'Place[]',
        ], true)) {
            return $value;
        }

        if (null === $value) {
            return null;
        }
        if ([] === $value) {
            return [];
        }

        $targetEntityClassName = match (s($type)->trimSuffix('[]')->toString()) {
            'Community' => Community::class,
            'Place' => Place::class,
            default => null,
        };
        $repo = match ($targetEntityClassName) {
            Community::class => $this->communityRepository,
            Place::class => $this->placeRepository,
            default => throw new \RuntimeException('Unknown type '.$type),
        };

        if (str_ends_with($type, '[]')) {
            // That's an array
            if (!is_array($value)) {
                throw new BadRequestHttpException($nameEnum->value.': should be an array');
            }

            // $instances = $repo->findBy(['id' => $value]);: does not work
            $instances = array_map(fn (string $id) => $repo->ofId(Uuid::fromString($id)), $value);
            $instances = array_filter($instances);

            if (count($instances) !== count($value)) {
                throw new FieldEntityNotFoundException($value);
            }

            return $instances;
        } else {
            // That's an object
            assert(is_string($value));
            $instance = $repo->ofId(Uuid::fromString($value));

            if (!$instance) {
                throw new FieldEntityNotFoundException($value);
            }

            return $instance;
        }
    }
}
