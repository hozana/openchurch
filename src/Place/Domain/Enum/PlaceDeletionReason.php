<?php

namespace App\Place\Domain\Enum;

enum PlaceDeletionReason: string
{
    case GARBAGE = 'garbage';
    case DUPLICATE = 'duplicate';
    case DESTROYED = 'destroyed';
    case DESECRATED = 'desecrated';
}
