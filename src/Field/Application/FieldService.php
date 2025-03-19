<?php

namespace App\Field\Application;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Agent\Domain\Model\Agent;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldPlace;
use App\Field\Domain\Exception\FieldEntityNotFoundException;
use App\Field\Domain\Exception\FieldInvalidNameException;
use App\Field\Domain\Exception\FieldParentWikidataIdNotFoundException;
use App\Field\Domain\Exception\FieldUnicityViolationException;
use App\Field\Domain\Model\Field;
use App\Field\Domain\Repository\FieldRepositoryInterface;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Place\Domain\Model\Place;
use App\FieldHolder\Place\Domain\Repository\PlaceRepositoryInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function Symfony\Component\String\s;

final class FieldService
{
    public function __construct(
        private readonly CommunityRepositoryInterface $communityRepository,
        private readonly PlaceRepositoryInterface $placeRepository,
        private readonly FieldRepositoryInterface $fieldRepo,
        private readonly ValidatorInterface $validator,
        private readonly Security $security,
    ) {
    }

    /**
     * @param Field[] $fieldPayloads
     */
    public function upsertFields(Place|Community $entity, array $fieldPayloads): void
    {
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

            $this->maybeTransformAlias($entity, $enumValue, $fieldPayload);
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
                && $this->fieldRepo->existOusideOf($entity->id, $enumValue, $field->value)
            ) {
                throw new FieldUnicityViolationException($field->name, $field->value);
            }

            $violations = $this->validator->validate($field);
            if (count($violations) > 0) {
                throw new ValidationException($violations);
            }

            $field->applyValue(); // Dynamically set the value to the correct property (intVal, stringVal, ...)
        }
    }

    private function getOrCreate(Place|Community $entity, FieldPlace|FieldCommunity $nameEnum, Agent $agent): Field
    {
        $field = $entity->getFieldByNameAndAgent($nameEnum, $agent);
        if (!$field) {
            $field = new Field();
            $field->agent = $agent;
            $this->fieldRepo->add($field);
            $entity->addField($field);
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
            default => throw new RuntimeException('Unknown type '.$type),
        };

        if (str_ends_with($type, '[]')) {
            // That's an array
            if (!is_array($value)) {
                throw new BadRequestHttpException($nameEnum->value.': should be an array');
            }

            $instances = $repo->ofIds(array_map(fn (string $id) => UuidV7::fromString($id), $value))->asCollection();

            if (count($instances) !== count($value)) {
                throw new FieldEntityNotFoundException($value);
            }

            return $instances->toArray();
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

    private function maybeTransformAlias(Place|Community $entity, FieldCommunity|FieldPlace &$enumValue, Field $fieldPayload): void
    {
        $aliases = match ($entity::class) {
            Place::class => FieldPlace::ALIASES,
            Community::class => FieldCommunity::ALIASES,
            default => null,
        };

        if (!array_key_exists($enumValue->name, $aliases)) {
            return;
        }

        $enumValue = $aliases[$enumValue->name];
        $fieldPayload->value = match ($fieldPayload->name) {
            FieldCommunity::PARENT_WIKIDATA_ID->value => $this->wikidataIdToCommunityId($fieldPayload->value),
            FieldPlace::PARENT_WIKIDATA_IDS->value => $this->wikidataIdsToCommunityIds($fieldPayload->value),
            default => null,
        };
        $fieldPayload->name = $enumValue->value;
    }

    private function wikidataIdToCommunityId(int $wikidataId): string
    {
        $fields = $this->fieldRepo->getNameValueFields(FieldCommunity::WIKIDATA_ID, $wikidataId);
        if (0 === count($fields)) {
            throw new FieldParentWikidataIdNotFoundException([$wikidataId]);
        }

        return $fields[0]->community->id->toString() ?? $fields[0]->place->id->toString();
    }

    /**
     * @param int[] $wikidataIds
     *
     * @return string[]
     */
    private function wikidataIdsToCommunityIds(array $wikidataIds): array
    {
        $fields = $this->fieldRepo->getNameValueFields(FieldCommunity::WIKIDATA_ID, $wikidataIds);
        $foundWikidataIds = array_map(fn (Field $field) => $field->getValue(), $fields);
        $missingWikidataIds = array_diff($wikidataIds, $foundWikidataIds);
        if (count($fields) !== count($wikidataIds)) {
            throw new FieldParentWikidataIdNotFoundException($missingWikidataIds);
        }

        return array_map(fn (Field $field) => $field->community->id->toString() ?? $field->place->id->toString(), $fields);
    }
}
