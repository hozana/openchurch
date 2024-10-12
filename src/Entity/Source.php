<?php

namespace App\Entity;

use App\Helper\Doctrine\EnumType;

class Source extends EnumType
{
    public const HUMAN = 'human';
    public const AI = 'ai';
    public const SCRAPER = 'scraper';

    public function getName(): string
    {
        return 'enum_source_type';
    }
}
