<?php

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
}
