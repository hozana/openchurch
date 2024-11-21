<?php

namespace App\Field\Domain\Enum;

use App\Shared\Infrastructure\Doctrine\DoctrineEnumType;

class FieldReliability extends DoctrineEnumType
{
    public const HIGH = 'high';
    public const MEDIUM = 'medium';
    public const LOW = 'low';

    public function getName(): string
    {
        return 'enum_reliability_type';
    }

    public static function compare($reliabilityA, $reliabilityB) {
        $reliabilityValues = [
            self::HIGH => 3,
            self::MEDIUM => 2,
            self::LOW => 1
        ];

        return $reliabilityValues[$reliabilityA] <=> $reliabilityValues[$reliabilityB];
    }
}
