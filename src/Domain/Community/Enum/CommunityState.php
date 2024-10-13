<?php

namespace App\Domain\Community\Enum;

enum CommunityState: string
{
    case ACTIVE = 'active';
    case DELETED = 'deleted';
}
