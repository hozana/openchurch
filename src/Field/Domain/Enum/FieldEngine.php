<?php

declare(strict_types=1);

namespace App\Field\Domain\Enum;

use App\Shared\Infrastructure\Doctrine\DoctrineEnumType;

class FieldEngine extends DoctrineEnumType
{
    public const HUMAN = 'human';

    public const AI = 'ai';

    public const SCRAPER = 'scraper';

    public function getName(): string
    {
        return 'enum_engine_type';
    }

    /**
     * Orders engines by trust: a human-entered value beats an AI one, which beats a scraped one.
     */
    public static function compare(string $engineA, string $engineB): int
    {
        $engineValues = [
            self::HUMAN => 1,
            self::AI => 2,
            self::SCRAPER => 3,
        ];

        return $engineValues[$engineA] <=> $engineValues[$engineB];
    }
}
