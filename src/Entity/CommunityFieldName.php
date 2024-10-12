<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;

enum CommunityFieldName: string
{
    public const TYPES = [
        self::NAME->value => Types::STRING,
        self::TYPE->value => [
            'parish',
            'parishGroup',
            'deanery',
            'sanctuary',
            'religiousCommunity',
            'congregation',
            'diocese',
        ],
        self::STATE->value => [
            'active',
            'deleted',
        ],
        self::DELETION_REASON->value => [
            'garbage',
            'duplicate',
            'dissolved',
        ],
        self::WEBSITE->value => Types::STRING,
        self::CONTACT_PHONE->value => Types::STRING,
        self::CONTACT_EMAIL->value => Types::STRING,
        self::CONTACT_ADDRESS->value => Types::STRING,
        self::CONTACT_ZIPCODE->value => Types::STRING,
        self::CONTACT_CITY->value => Types::STRING,
        self::MESSESINFO_ID->value => Types::INTEGER,
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
    case MESSESINFO_ID = 'messesInfoId';
    case WIKIDATA_ID = 'wikidataId';
    case PARENT_COMMUNITY_ID = 'parentCommunityId';
    case REPLACES = 'replaces';

    public function getType(): string|array
    {
        return self::TYPES[$this->value];
    }
}
