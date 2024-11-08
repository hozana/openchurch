<?php

namespace App\Shared\Infrastructure\Doctrine;

use App\Shared\Domain\Enum\EnumTrait;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use LogicException;

/**
 * Doctrine enum type.
 *
 * Transforms a PHP enum-like class to an ENUM(...) MySQL column.
 */
abstract class DoctrineEnumType extends Type
{
    use EnumTrait;

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $constants = self::constants();

        // Ensure that none of the values is an integer
        if (!empty(array_filter(array_map('is_int', $constants)))) {
            throw new LogicException("Please don't use integer enums in MySQL.");
        }

        $values = array_map(fn ($val) => "'$val'", $constants);

        return 'ENUM('.implode(', ', $values).')';
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        self::ensureValid($value, true);

        return $value;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    abstract public function getName(): string;
}
