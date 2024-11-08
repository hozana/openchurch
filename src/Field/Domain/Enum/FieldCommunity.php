<?php

namespace App\Field\Domain\Enum;

use App\Community\Domain\Enum\CommunityDeletionReason;
use App\Community\Domain\Enum\CommunityState;
use App\Community\Domain\Enum\CommunityType;
use Doctrine\DBAL\Types\Types;

enum FieldCommunity: string
{
    public const TYPES = [
        self::NAME->value => Types::STRING,
        self::TYPE->value => CommunityType::class,
        self::STATE->value => CommunityState::class,
        self::DELETION_REASON->value => CommunityDeletionReason::class,
        self::WEBSITE->value => Types::STRING,
        self::CONTACT_PHONE->value => Types::STRING,
        self::CONTACT_EMAIL->value => Types::STRING,
        self::CONTACT_ADDRESS->value => Types::STRING,
        self::CONTACT_ZIPCODE->value => Types::STRING,
        self::CONTACT_CITY->value => Types::STRING,
        self::CONTACT_COUNTRY_CODE->value => Types::STRING,
        self::MESSESINFO_ID->value => Types::STRING,
        self::WIKIDATA_ID->value => Types::INTEGER,
        self::PARENT_COMMUNITY_ID->value => 'Community',
        self::REPLACES->value => 'Community[]',
    ];

    case NAME = 'name';
    case TYPE = 'type';
    case STATE = 'state';
    case DELETION_REASON = 'deletionReason';
    case WEBSITE = 'website';
    case CONTACT_PHONE = 'contactPhone';
    case CONTACT_EMAIL = 'contactEmail';
    case CONTACT_ADDRESS = 'contactAddress';
    case CONTACT_ZIPCODE = 'contactZipcode';
    case CONTACT_CITY = 'contactCity';
    case CONTACT_COUNTRY_CODE = 'contactCountryCode';
    case MESSESINFO_ID = 'messesInfoId';
    case WIKIDATA_ID = 'wikidataId';
    case PARENT_COMMUNITY_ID = 'parentCommunityId';
    case REPLACES = 'replaces';

    public function getType(): string|array
    {
        return self::TYPES[$this->value];
    }
}
