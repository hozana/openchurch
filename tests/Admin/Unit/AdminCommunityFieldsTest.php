<?php

declare(strict_types=1);

namespace App\Tests\Admin\Unit;

use App\Admin\Application\AdminCommunityFields;
use App\Field\Domain\Enum\FieldCommunity;
use PHPUnit\Framework\TestCase;

final class AdminCommunityFieldsTest extends TestCase
{
    public function testAliasesAreNotDisplayed(): void
    {
        self::assertNotContains(FieldCommunity::PARENT_WIKIDATA_ID, AdminCommunityFields::displayed());
        self::assertContains(FieldCommunity::PARENT_COMMUNITY_ID, AdminCommunityFields::displayed());
    }

    public function testSynchronisedAndStructuralFieldsAreReadOnly(): void
    {
        foreach ([FieldCommunity::TYPE, FieldCommunity::WIKIDATA_ID, FieldCommunity::MESSESINFO_ID, FieldCommunity::WIKIDATA_UPDATED_AT] as $field) {
            self::assertContains($field, AdminCommunityFields::displayed());
            self::assertFalse(AdminCommunityFields::isEditable($field), $field->value);
        }
    }

    public function testEditableFields(): void
    {
        self::assertSame([
            FieldCommunity::NAME,
            FieldCommunity::STATE,
            FieldCommunity::DELETION_REASON,
            FieldCommunity::WEBSITE,
            FieldCommunity::CONTACT_PHONE,
            FieldCommunity::CONTACT_EMAIL,
            FieldCommunity::CONTACT_ADDRESS,
            FieldCommunity::CONTACT_ZIPCODE,
            FieldCommunity::CONTACT_CITY,
            FieldCommunity::CONTACT_COUNTRY_CODE,
            FieldCommunity::PARENT_COMMUNITY_ID,
            FieldCommunity::REPLACES,
        ], AdminCommunityFields::editable());
    }
}
