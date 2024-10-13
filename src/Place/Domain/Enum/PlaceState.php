<?php

namespace App\Place\Domain\Enum;

enum PlaceState: string
{
    case ACTIVE = 'active';
    case DELETED = 'deleted';
}
