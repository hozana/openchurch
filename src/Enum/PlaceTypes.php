<?php

namespace App\Enum;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

final class PlaceTypes extends AbstractEnumType
{
    public const CITY = 'city';
    public const COUNTRY = 'country';
    public const STATE = 'state';
    public const AREA = 'area';
    public const UNKNOWN = 'unknown';

    protected static $choices = [
        self::CITY => self::CITY,
        self::COUNTRY => self::COUNTRY,
        self::STATE => self::STATE,
        self::AREA => self::AREA,
        self::UNKNOWN => self::UNKNOWN,
    ];
}
