<?php

namespace App\Community\Domain\Enum;

enum CommunityState: string
{
    case ACTIVE = 'active';
    case DELETED = 'deleted';
}
