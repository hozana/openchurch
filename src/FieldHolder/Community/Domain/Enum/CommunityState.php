<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Domain\Enum;

enum CommunityState: string
{
    case ACTIVE = 'active';
    case DELETED = 'deleted';
}
