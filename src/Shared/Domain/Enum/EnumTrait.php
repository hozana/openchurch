<?php

namespace App\Shared\Domain\Enum;

use InvalidArgumentException;
use ReflectionClass;

/**
 * Inspired and adapted from @see NamedEnum
 * getName method is used by EnumType (Doctrine type name), so name was renamed into label:
 *  - $VALUE_NAMES -> $LABELS
 *  - getNames() -> labels()
 *  - getName() -> label().
 */
trait EnumTrait
{
    /** @var array<string, string> */
    protected static array $LABELS = [];

    /**
     * Get all the values, indexed by their constant name as defined in the class.
     *
     * @return array<mixed>
     */
    public static function constants(): array
    {
        return new ReflectionClass(static::class)->getConstants();
    }

    /**
     * @return array<string>
     */
    public static function labels(): array
    {
        return static::$LABELS;
    }

    /**
     * Get the label of a constant name, or null if the value doesn't exist.
     */
    public static function label(mixed $value): ?string
    {
        return static::$LABELS[$value] ?? null;
    }

    /**
     * Get an array of all the values.
     *
     * @return array<mixed>
     */
    public static function values(): array
    {
        return array_values(static::constants());
    }

    /**
     * Get an array of all the values indexed by name
     * (especially useful to use in a ChoiceType field in a Symfony Form).
     *
     * @return array<string>
     */
    public static function choices(): array
    {
        return array_flip(static::$LABELS);
    }

    /**
     * Returns true if the specified value is declared as one of this enum values.
     */
    public static function isValid(mixed $value, bool $nullable = false, bool $strictCheck = true): bool
    {
        // Null and nullable is allowed
        if ($nullable && null === $value) {
            return true;
        }

        return in_array($value, self::constants(), $strictCheck);
    }

    /**
     * Throws an exception if the specified value is not declared as one of this enum values.
     */
    public static function ensureValid(mixed $value, bool $nullable = false, bool $strictCheck = true): void
    {
        if (!self::isValid($value, $nullable, $strictCheck)) {
            $callerContext = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1];
            $callerMethod = array_key_exists('class', $callerContext)
                ? $callerContext['class'].$callerContext['type'].$callerContext['function']
                : $callerContext['file'].'::'.$callerContext['function'];

            throw new InvalidArgumentException(sprintf('Invalid argument provided to %s - expected one of "%s", got "%s"', $callerMethod, implode(', ', self::constants()), var_export($value, true)));
        }
    }
}
