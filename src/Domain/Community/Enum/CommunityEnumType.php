<?php

namespace App\Domain\Community\Enum;

use App\Helper\Doctrine\EnumType;

enum CommunityEnumType
{
    public const PARISH = 'parish';
    public const PARISH_GROUP = 'parishGroup';
    public const DEANERY = 'deanery';
    public const SANCTUARY = 'sanctuary';
    public const RELIGIOUS_COMMUNITY = 'religiousCommunity';
    public const CONGREGATION = 'congregation';
    public const DIOCESE = 'diocese';

    public function getName(): string
    {
        return 'enum_community_type';
    }
}
