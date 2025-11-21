<?php

namespace App\Field\Domain\Enum;

use App\FieldHolder\Place\Domain\Enum\PlaceDeletionReason;
use App\FieldHolder\Place\Domain\Enum\PlaceState;
use App\FieldHolder\Place\Domain\Enum\PlaceType;
use Doctrine\DBAL\Types\Types;

enum FieldPlace: string
{
    public const TYPES = [
        self::NAME->value => Types::STRING,
        self::TYPE->value => PlaceType::class,
        self::WEBSITE->value => Types::STRING,
        self::CAPACITY->value => Types::STRING,
        self::ADDRESS->value => Types::STRING,
        self::ZIPCODE->value => Types::STRING,
        self::CITY->value => Types::STRING,
        self::COUNTRY_CODE->value => Types::STRING,
        self::LATITUDE->value => Types::FLOAT,
        self::LONGITUDE->value => Types::FLOAT,
        self::MESSESINFO_ID->value => Types::STRING,
        self::WIKIDATA_ID->value => Types::INTEGER,
        self::STATE->value => PlaceState::class,
        self::DELETION_REASON->value => PlaceDeletionReason::class,
        self::REPLACES->value => 'Place[]',
        self::PARENT_COMMUNITIES->value => 'Community[]',
        self::PARENT_WIKIDATA_IDS->value => 'Community[]',
        self::WIKIDATA_UPDATED_AT->value => Types::DATETIME_IMMUTABLE,
    ];

    public const ALIASES = [
        self::PARENT_WIKIDATA_IDS->name => self::PARENT_COMMUNITIES,
    ];

    case NAME = 'name';
    case TYPE = 'type';
    case WEBSITE = 'website';
    case CAPACITY = 'capacity';
    case ADDRESS = 'address';
    case ZIPCODE = 'zipcode';
    case CITY = 'city';
    case COUNTRY_CODE = 'countryCode';
    case LATITUDE = 'latitude';
    case LONGITUDE = 'longitude';
    case MESSESINFO_ID = 'messesInfoId';
    case WIKIDATA_ID = 'wikidataId';
    case STATE = 'state';
    case DELETION_REASON = 'deletionReason';
    case REPLACES = 'replaces';
    case PARENT_COMMUNITIES = 'parentCommunities';
    case PARENT_WIKIDATA_IDS = 'parentWikidataIds';
    case WIKIDATA_UPDATED_AT = 'wikidataUpdatedAt';

    public function getType(): string
    {
        return self::TYPES[$this->value];
    }
}
