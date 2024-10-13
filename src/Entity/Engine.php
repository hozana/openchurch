<?php

namespace App\Entity;

use App\Helper\Doctrine\EnumType;

class Engine extends EnumType
{
    public const HUMAN = 'human';
    public const AI = 'ai';
    public const SCRAPER = 'scraper';

    public function getName(): string
    {
        return 'enum_engine_type';
    }
}
