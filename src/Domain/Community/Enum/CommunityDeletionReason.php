<?php

namespace App\Domain\Community\Enum;

enum CommunityDeletionReason: string
{
    case GARBAGE = 'garbage';
    case DUPLICATE = 'duplicate';
    case DISSOLVED = 'dissolved';
}
