<?php

namespace App\Community\Domain\Enum;

enum CommunityIndex: string
{
    case PARISH = 'parish';

    public function getName(): string
    {
        return 'enum_community_index';
    }
}
