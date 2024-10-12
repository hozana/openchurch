<?php

namespace App\Entity;

use App\Helper\Doctrine\EnumType;

class Reliability extends EnumType
{
    public const HIGH = 'high';
    public const MEDIUM = 'medium';
    public const LOW = 'low';

    public function getName(): string
    {
        return 'enum_reliability_type';
    }
}
