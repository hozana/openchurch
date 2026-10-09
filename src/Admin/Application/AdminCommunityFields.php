<?php

declare(strict_types=1);

namespace App\Admin\Application;

use App\Field\Domain\Enum\FieldCommunity;

/**
 * Which community fields the admin backend displays, and which ones it lets admins edit.
 */
final class AdminCommunityFields
{
    private const array READ_ONLY = [
        FieldCommunity::TYPE,
        FieldCommunity::WIKIDATA_ID,
        FieldCommunity::MESSESINFO_ID,
        FieldCommunity::WIKIDATA_UPDATED_AT,
    ];

    private const array HIDDEN = [
        FieldCommunity::PARENT_WIKIDATA_ID,
    ];

    /**
     * @return list<FieldCommunity>
     */
    public static function displayed(): array
    {
        return array_values(array_filter(
            FieldCommunity::cases(),
            static fn (FieldCommunity $field): bool => !in_array($field, self::HIDDEN, true),
        ));
    }

    /**
     * @return list<FieldCommunity>
     */
    public static function editable(): array
    {
        return array_values(array_filter(
            self::displayed(),
            static fn (FieldCommunity $field): bool => !in_array($field, self::READ_ONLY, true),
        ));
    }

    public static function isEditable(FieldCommunity $field): bool
    {
        return in_array($field, self::editable(), true);
    }

    public static function label(FieldCommunity $field): string
    {
        return match ($field) {
            FieldCommunity::NAME => 'Nom',
            FieldCommunity::TYPE => 'Type',
            FieldCommunity::STATE => 'État',
            FieldCommunity::DELETION_REASON => 'Motif de suppression',
            FieldCommunity::WEBSITE => 'Site web',
            FieldCommunity::CONTACT_PHONE => 'Téléphone',
            FieldCommunity::CONTACT_EMAIL => 'Email',
            FieldCommunity::CONTACT_ADDRESS => 'Adresse',
            FieldCommunity::CONTACT_ZIPCODE => 'Code postal',
            FieldCommunity::CONTACT_CITY => 'Ville',
            FieldCommunity::CONTACT_COUNTRY_CODE => 'Code pays',
            FieldCommunity::MESSESINFO_ID => 'Identifiant Messes.info',
            FieldCommunity::WIKIDATA_ID => 'Identifiant Wikidata',
            FieldCommunity::WIKIDATA_UPDATED_AT => 'Mise à jour Wikidata',
            FieldCommunity::PARENT_COMMUNITY_ID => 'Diocèse',
            FieldCommunity::PARENT_WIKIDATA_ID => 'Identifiant Wikidata du parent',
            FieldCommunity::REPLACES => 'Remplace',
        };
    }
}
