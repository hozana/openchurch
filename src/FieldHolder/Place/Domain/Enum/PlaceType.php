<?php

namespace App\FieldHolder\Place\Domain\Enum;

enum PlaceType: string
{
    case CHURCH = 'church';
    case CATHEDRAL = 'cathedral';
    case CHAPEL = 'chapel';
    case PARISH_HALL = 'parishHall';
    case ABBEY = 'abbey';
    case CRYPT = 'crypt';
}
