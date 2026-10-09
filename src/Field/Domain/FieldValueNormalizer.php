<?php

declare(strict_types=1);

namespace App\Field\Domain;

use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Place\Domain\Model\Place;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final class FieldValueNormalizer
{
    /**
     * @return string|int|float|list<string>|null
     */
    public static function normalize(mixed $value): string|int|float|array|null
    {
        if ($value instanceof Collection) {
            $value = $value->toArray();
        }

        return match (true) {
            null === $value, '' === $value, [] === $value => null,
            is_string($value), is_int($value), is_float($value) => $value,
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
            $value instanceof Community, $value instanceof Place => $value->id?->toString(),
            is_array($value) => array_values(array_unique(array_map(self::normalizeRelated(...), $value))),
            default => throw new InvalidArgumentException(sprintf('Cannot normalize a field value of type %s', get_debug_type($value))),
        };
    }

    private static function normalizeRelated(mixed $value): string
    {
        $id = match (true) {
            $value instanceof Community, $value instanceof Place => $value->id?->toString(),
            // Ids are compared as strings: give them their canonical (lowercase) form
            is_string($value) && Uuid::isValid($value) => Uuid::fromString($value)->toRfc4122(),
            is_string($value) => $value,
            default => null,
        };

        return $id ?? throw new InvalidArgumentException(sprintf('Cannot normalize a related field value of type %s', get_debug_type($value)));
    }
}
