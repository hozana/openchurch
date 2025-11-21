<?php

namespace App\FieldHolder\Community\Domain\Enum;

enum CommunityDeletionReason: string
{
    case GARBAGE = 'garbage';
    case DUPLICATE = 'duplicate';
    case DISSOLVED = 'dissolved';
}
