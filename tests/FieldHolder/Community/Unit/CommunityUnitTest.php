<?php

namespace App\Tests\FieldHolder\Community\Unit;

use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Enum\CommunityState;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\FieldHolder\Community\DummyFactory\DummyCommunityFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class CommunityUnitTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testStateDeletedWithoutReason(): void
    {
        $community = DummyCommunityFactory::new()->withoutPersisting()->create(['fields' => [
            DummyFieldFactory::new()->withoutPersisting()->create([
                'name' => FieldCommunity::STATE->value, 'stringVal' => CommunityState::DELETED->value,
            ]),
        ]]);

        $violations = $this->validator->validate($community);
        static::assertCount(1, $violations);
        static::assertEquals('Deletion reason is mandatory when reporting a state=deleted state.', $violations->get(0)->getMessage());
    }

    public function testCountryCodeValidation(): void
    {
        $community = DummyCommunityFactory::new()->withoutPersisting()->create(['fields' => [
            DummyFieldFactory::new()->withoutPersisting()->create([
                'name' => FieldCommunity::CONTACT_COUNTRY_CODE->value, 'stringVal' => -1,
            ]),
        ]]);

        $violations = $this->validator->validate($community);
        static::assertCount(1, $violations);
        static::assertEquals("Country code '-1' is not valid.", $violations->get(0)->getMessage());
    }

    public function testGetMostTrustableFieldByName(): void
    {
        $community = DummyCommunityFactory::new()->withoutPersisting()->create(['fields' => [
            DummyFieldFactory::new()->withoutPersisting()->create([
                'name' => FieldCommunity::NAME->value, 'stringVal' => 'low reliability name',
                'reliability' => FieldReliability::LOW,
            ]),
            DummyFieldFactory::new()->withoutPersisting()->create([
                'name' => FieldCommunity::NAME->value, 'stringVal' => 'high reliability name',
                'reliability' => FieldReliability::HIGH,
            ]),
            DummyFieldFactory::new()->withoutPersisting()->create([
                'name' => FieldCommunity::NAME->value, 'stringVal' => 'medium reliability name',
                'reliability' => FieldReliability::MEDIUM,
            ]),
        ],
        ])->_real();

        $result = $community->getMostTrustableFieldByName(FieldCommunity::NAME);
        static::assertEquals('high reliability name', $result->getValue());

        $community = DummyCommunityFactory::new()->withoutPersisting()->create()->_real();
        $result = $community->getMostTrustableFieldByName(FieldCommunity::NAME);
        static::assertEmpty($result);
    }

    public function testGetFieldsByName(): void
    {
        $community = DummyCommunityFactory::new()->withoutPersisting()->create(['fields' => [
            DummyFieldFactory::new()->withoutPersisting()->create([
                'name' => FieldCommunity::NAME->value, 'stringVal' => 'low reliability name',
                'reliability' => FieldReliability::LOW,
            ]),
            DummyFieldFactory::new()->withoutPersisting()->create([
                'name' => FieldCommunity::NAME->value, 'stringVal' => 'high reliability name',
                'reliability' => FieldReliability::HIGH,
            ]),
            DummyFieldFactory::new()->withoutPersisting()->create([
                'name' => FieldCommunity::NAME->value, 'stringVal' => 'medium reliability name',
                'reliability' => FieldReliability::MEDIUM,
            ]),
            DummyFieldFactory::new()->withoutPersisting()->create([
                'name' => FieldCommunity::CONTACT_ADDRESS->value, 'stringVal' => 'adress...',
                'reliability' => FieldReliability::MEDIUM,
            ]),
            DummyFieldFactory::new()->withoutPersisting()->create([
                'name' => FieldCommunity::CONTACT_CITY->value, 'stringVal' => 'city...',
                'reliability' => FieldReliability::MEDIUM,
            ]),
        ],
        ])->_real();

        $results = $community->getFieldsByName(FieldCommunity::NAME);
        static::assertCount(3, $results);

        foreach ($results as $result) {
            static::assertEquals(FieldCommunity::NAME->value, $result->name);
        }
    }

    public function testRemoveField(): void
    {
        $field = DummyFieldFactory::new()->withoutPersisting()->create([
            'name' => FieldCommunity::NAME->value,
            Field::getPropertyName(FieldCommunity::NAME) => 'mon nom',
        ])->_real();

        $community = DummyCommunityFactory::new()->withoutPersisting()->create([
            'fields' => [$field],
        ])->_real();

        static::assertCount(1, $community->fields);
        $community->removeField($field);
        static::assertCount(0, $community->fields);
        static::assertNull($field->place);
    }
}
