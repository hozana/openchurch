<?php

namespace App\Domain\Place\Enum;

enum PlaceState: string
{
    case ACTIVE = 'active';
    case DELETED = 'deleted';
}
