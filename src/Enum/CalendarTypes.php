<?php

namespace App\Enum;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

final class CalendarTypes extends AbstractEnumType
{
    public const MASS = 'mass';
    public const CONFESSION = 'confession';
    public const ADORATION = 'adoration';
    public const UNKNOWN = 'unknown';

    protected static $choices = [
        self::MASS => self::MASS,
        self::CONFESSION => self::CONFESSION,
        self::ADORATION => self::ADORATION,
        self::UNKNOWN => self::UNKNOWN,
    ];
}
