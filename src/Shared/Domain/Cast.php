<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use Stringable;

/**
 * Narrowing helpers for mixed values (decoded payloads, raw DQL results, environment
 * variables...) that have to be passed to a typed signature.
 */
final class Cast
{
    /**
     * Renders any value as a displayable string, without ever failing.
     * Values that have no string representation (array, object, resource) yield an empty string.
     */
    public static function toString(mixed $value): string
    {
        if (is_scalar($value) || $value instanceof Stringable) {
            return (string) $value;
        }

        return '';
    }

    /**
     * Nullable variant: keeps the distinction between "absent" and "empty string".
     */
    public static function toNullableString(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        return self::toString($value);
    }

    /**
     * Returns the value as an int when it already is one, or when it is a string PHP itself
     * would turn into an integer array key (canonical decimal, optional minus, no leading zero).
     * Returns null otherwise, so the caller has to decide what an unusable value means.
     *
     * Prefer this over toInt() whenever the value is used as an identifier.
     */
    public static function toIntOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && 1 === preg_match('/^-?(0|[1-9]\d*)$/', $value)) {
            return (int) $value;
        }

        return null;
    }

    /**
     * Renders an integer from a numeric value, 0 otherwise.
     */
    public static function toInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return 0;
    }
}
