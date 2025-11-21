<?php

declare(strict_types=1);

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

final class CommunityUnitTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    public function testStateDeletedWithoutReason(): void
    {
        $community = DummyCommunityFactory::new()->withoutPersisting()->create(['fields' => [
            DummyFieldFactory::new()->withoutPersisting()->create([
                'name' => FieldCommunity::STATE->value, 'stringVal' => CommunityState::DELETED->value,
            ]),
        ]]);

        $violations = $this->validator->validate($community);
        self::assertCount(1, $violations);
        self::assertSame('Deletion reason is mandatory when reporting a state=deleted state.', $violations->get(0)->getMessage());
    }

    public function testCountryCodeValidation(): void
    {
        $community = DummyCommunityFactory::new()->withoutPersisting()->create(['fields' => [
            DummyFieldFactory::new()->withoutPersisting()->create([
                'name' => FieldCommunity::CONTACT_COUNTRY_CODE->value, 'stringVal' => -1,
            ]),
        ]]);

        $violations = $this->validator->validate($community);
        self::assertCount(1, $violations);
        self::assertSame("Country code '-1' is not valid.", $violations->get(0)->getMessage());
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
        ]);

        $result = $community->getMostTrustableFieldByName(FieldCommunity::NAME);
        $this->assertInstanceOf(Field::class, $result);
        self::assertEquals('high reliability name', $result->getValue());

        $community = DummyCommunityFactory::new()->withoutPersisting()->create();
        $result = $community->getMostTrustableFieldByName(FieldCommunity::NAME);
        self::assertEmpty($result);
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
        ]);

        $results = $community->getFieldsByName(FieldCommunity::NAME);
        self::assertCount(3, $results);

        foreach ($results as $result) {
            self::assertEquals(FieldCommunity::NAME->value, $result->name);
        }
    }

    public function testRemoveField(): void
    {
        $field = DummyFieldFactory::new()->withoutPersisting()->create([
            'name' => FieldCommunity::NAME->value,
            Field::getPropertyName(FieldCommunity::NAME) => 'mon nom',
        ]);

        $community = DummyCommunityFactory::new()->withoutPersisting()->create([
            'fields' => [$field],
        ]);

        self::assertCount(1, $community->fields);
        $community->removeField($field);
        self::assertCount(0, $community->fields);
        self::assertNull($field->place);
    }
}
