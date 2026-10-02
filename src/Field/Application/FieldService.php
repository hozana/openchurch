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
use App\Field\Domain\FieldValueNormalizer;
use App\Field\Domain\Model\Field;
use App\Field\Domain\Repository\FieldRepositoryInterface;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Place\Domain\Model\Place;
use App\FieldHolder\Place\Domain\Repository\PlaceRepositoryInterface;
use App\Shared\Domain\Cast;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function Symfony\Component\String\s;

final readonly class FieldService
{
    public function __construct(
        private CommunityRepositoryInterface $communityRepository,
        private PlaceRepositoryInterface $placeRepository,
        private FieldRepositoryInterface $fieldRepo,
        private ValidatorInterface $validator,
        private Security $security,
    ) {
    }

    /**
     * Writes the fields on behalf of $agent, or of the authenticated agent when none is given.
     *
     * @param Field[] $fieldPayloads
     */
    public function upsertFields(Place|Community $entity, array $fieldPayloads, ?Agent $agent = null): void
    {
        $agent ??= $this->authenticatedAgent();

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
            $field = $entity->getFieldByNameAndAgent($enumValue, $agent);
            $previousState = null !== $field ? self::state($field) : null;
            $field ??= $this->create($entity, $agent);
            $value = $this->maybeTransformEntities($enumValue, $fieldPayload->value);
            if ($entity instanceof Community) {
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

            // Unique constraints validation (TODO use custom Assert instead)
            if (null !== $field->value
                && null !== $entity->id
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

            // The update date tells which value is the most recent one: sending the same value again doesn't change it
            if (self::state($field) !== $previousState) {
                $field->touch();
            }
        }
    }

    private function authenticatedAgent(): Agent
    {
        $user = $this->security->getUser();
        if (!$user instanceof Agent) {
            throw new RuntimeException('Fields can only be written on behalf of an agent.');
        }

        return $user;
    }

    private function create(Place|Community $entity, Agent $agent): Field
    {
        $field = new Field();
        $field->agent = $agent;
        $this->fieldRepo->add($field);
        $entity->addField($field);

        return $field;
    }

    /**
     * What an upsert can change on a field.
     *
     * @return list<mixed>
     */
    private static function state(Field $field): array
    {
        return [
            FieldValueNormalizer::normalize($field->getValue()),
            $field->engine,
            $field->reliability,
            $field->source,
            $field->explanation,
        ];
    }

    /**
     * Resolves ids into entities for Community/Place typed fields; for any other type the raw
     * value is returned untouched.
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

            $instances = $repo->ofIds(array_map(
                static function (mixed $id) use ($nameEnum): Uuid {
                    if (!is_string($id) || !Uuid::isValid($id)) {
                        throw new BadRequestHttpException($nameEnum->value.': should be an array of id strings');
                    }

                    return Uuid::fromString($id);
                },
                $value
            ))->asCollection();

            if (count($instances) !== count($value)) {
                throw new FieldEntityNotFoundException($value);
            }

            return $instances->toArray();
        }
        // That's an object
        if (!is_string($value) || !Uuid::isValid($value)) {
            throw new BadRequestHttpException($nameEnum->value.': should be an id string');
        }
        $instance = $repo->ofId(Uuid::fromString($value));

        if (null === $instance) {
            throw new FieldEntityNotFoundException($value);
        }

        return $instance;
    }

    private function maybeTransformAlias(Place|Community $entity, FieldCommunity|FieldPlace &$enumValue, Field $fieldPayload): void
    {
        $aliases = $entity instanceof Community ? FieldCommunity::ALIASES : FieldPlace::ALIASES;

        if (!array_key_exists($enumValue->name, $aliases)) {
            return;
        }

        $enumValue = $aliases[$enumValue->name];
        $fieldPayload->value = match ($fieldPayload->name) {
            FieldCommunity::PARENT_WIKIDATA_ID->value => $this->wikidataIdToCommunityId($this->toWikidataId($fieldPayload->value)),
            FieldPlace::PARENT_WIKIDATA_IDS->value => $this->wikidataIdsToCommunityIds(
                array_map($this->toWikidataId(...), is_array($fieldPayload->value) ? $fieldPayload->value : [])
            ),
            default => null,
        };
        $fieldPayload->name = $enumValue->value;
    }

    /**
     * A wikidata id is an integer, accepted either as a JSON number or as its string form;
     * anything else in the payload is a client error.
     */
    private function toWikidataId(mixed $value): int
    {
        return Cast::toIntOrNull($value)
            ?? throw new BadRequestHttpException(sprintf('wikidataId should be an integer, %s given', get_debug_type($value)));
    }

    private function wikidataIdToCommunityId(int $wikidataId): string
    {
        $fields = $this->fieldRepo->getNameValueFields(FieldCommunity::WIKIDATA_ID, $wikidataId);
        if ([] === $fields) {
            throw new FieldParentWikidataIdNotFoundException([$wikidataId]);
        }

        return $this->holderIdOf($fields[0])
            ?? throw new FieldParentWikidataIdNotFoundException([$wikidataId]);
    }

    /**
     * Id of the Community or Place the field is attached to.
     */
    private function holderIdOf(Field $field): ?string
    {
        return $field->community?->id?->toString() ?? $field->place?->id?->toString();
    }

    /**
     * @param int[] $wikidataIds
     *
     * @return string[]
     */
    private function wikidataIdsToCommunityIds(array $wikidataIds): array
    {
        $fields = $this->fieldRepo->getNameValueFields(FieldCommunity::WIKIDATA_ID, $wikidataIds);
        $foundWikidataIds = array_values(array_filter(
            array_map(static fn (Field $field): mixed => $field->getValue(), $fields),
            is_int(...)
        ));
        $missingWikidataIds = array_diff($wikidataIds, $foundWikidataIds);
        if (count($fields) !== count($wikidataIds)) {
            throw new FieldParentWikidataIdNotFoundException($missingWikidataIds);
        }

        return array_map(fn (Field $field): string => $this->holderIdOf($field) ?? '', $fields);
    }
}
