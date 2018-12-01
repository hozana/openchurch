<?php

namespace App\Enum;

use Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType;

final class Rite extends AbstractEnumType
{
    public const ROMAN_RITE_ORDINARY = 1;
    public const ROMAN_RITE_EXTRAORDINARY = 2;
    public const AMBROSIAN_RITE = 3;
    public const DOMINICAN_RITE = 4;
    public const CARTHUSIAN_RITE = 5;
    public const MOZARABIC_RITE = 6;
    public const RITE_OF_BRAGA = 7;
    public const ZAIRE_USE = 8;
    public const ANGLICAN_USE = 9;
    public const COPTIC_RITE = 10;
    public const BYZANTINE_RITE = 11;
    public const MARONITE_RITE = 12;
    public const CHALDEAN_RITE = 13;
    public const ARMENIAN_RITE = 14;
    public const GE_EZ_RITE = 15;

    protected static $choices = [
        self::ROMAN_RITE_ORDINARY => self::ROMAN_RITE_ORDINARY,
        self::ROMAN_RITE_EXTRAORDINARY => self::ROMAN_RITE_EXTRAORDINARY,
        self::AMBROSIAN_RITE => self::AMBROSIAN_RITE,
        self::DOMINICAN_RITE => self::DOMINICAN_RITE,
        self::CARTHUSIAN_RITE => self::CARTHUSIAN_RITE,
        self::MOZARABIC_RITE => self::MOZARABIC_RITE,
        self::RITE_OF_BRAGA => self::RITE_OF_BRAGA,
        self::ZAIRE_USE => self::ZAIRE_USE,
        self::ANGLICAN_USE => self::ANGLICAN_USE,
        self::COPTIC_RITE => self::COPTIC_RITE,
        self::BYZANTINE_RITE => self::BYZANTINE_RITE,
        self::MARONITE_RITE => self::MARONITE_RITE,
        self::CHALDEAN_RITE => self::CHALDEAN_RITE,
        self::ARMENIAN_RITE => self::ARMENIAN_RITE,
        self::GE_EZ_RITE => self::GE_EZ_RITE,
    ];
}
