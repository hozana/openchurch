<?php

namespace App\FieldHolder\Community\Domain\Enum;

enum CommunityType: string
{
    case PARISH = 'parish';
    case PARISH_GROUP = 'parishGroup';
    case DEANERY = 'deanery';
    case SANCTUARY = 'sanctuary';
    case RELIGIOUS_COMMUNITY = 'religiousCommunity';
    case CONGREGATION = 'congregation';
    case DIOCESE = 'diocese';
}
