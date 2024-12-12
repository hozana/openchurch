<?php

namespace App\Tests\Community\Unit;

use App\Community\Domain\Enum\CommunityState;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Model\Field;
use App\Tests\Community\DummyFactory\DummyCommunityFactory;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

use function Zenstruck\Foundry\Persistence\flush_after;

class CommunityTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testStateDeletedWithoutReason(): void
    {
        $community = DummyCommunityFactory::createOne(['fields' => [
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::STATE->value, 'stringVal' => CommunityState::DELETED->value,
            ]),
        ]]);

        $violations = $this->validator->validate($community);
        static::assertCount(1, $violations);
        static::assertEquals('Deletion reason is mandatory when reporting a state=deleted state.', $violations->get(0)->getMessage());
    }

    public function testCountryCodeValidation(): void
    {
        $community = DummyCommunityFactory::createOne(['fields' => [
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::CONTACT_COUNTRY_CODE->value, 'stringVal' => -1,
            ]),
        ]]);

        $violations = $this->validator->validate($community);
        static::assertCount(1, $violations);
        static::assertEquals("Country code '-1' is not valid.", $violations->get(0)->getMessage());
    }

    public function testGetMostTrustableFieldByName(): void
    {
        $community = flush_after(fn () => DummyCommunityFactory::createOne(['fields' => [
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::NAME->value, 'stringVal' => 'low reliability name',
                'reliability' => FieldReliability::LOW,
            ]),
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::NAME->value, 'stringVal' => 'high reliability name',
                'reliability' => FieldReliability::HIGH,
            ]),
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::NAME->value, 'stringVal' => 'medium reliability name',
                'reliability' => FieldReliability::MEDIUM,
            ]),
        ],
        ])->_real()
        );

        $result = $community->getMostTrustableFieldByName(FieldCommunity::NAME);
        static::assertEquals('high reliability name', $result->getValue());

        $community = DummyCommunityFactory::createOne();
        $result = $community->getMostTrustableFieldByName(FieldCommunity::NAME);
        static::assertEmpty($result);
    }

    public function testGetFieldsByName(): void
    {
        $community = flush_after(fn () => DummyCommunityFactory::createOne(['fields' => [
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::NAME->value, 'stringVal' => 'low reliability name',
                'reliability' => FieldReliability::LOW,
            ]),
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::NAME->value, 'stringVal' => 'high reliability name',
                'reliability' => FieldReliability::HIGH,
            ]),
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::NAME->value, 'stringVal' => 'medium reliability name',
                'reliability' => FieldReliability::MEDIUM,
            ]),
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::CONTACT_ADDRESS->value, 'stringVal' => 'adress...',
                'reliability' => FieldReliability::MEDIUM,
            ]),
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::CONTACT_CITY->value, 'stringVal' => 'city...',
                'reliability' => FieldReliability::MEDIUM,
            ]),
        ],
        ])->_real()
        );

        $results = $community->getFieldsByName(FieldCommunity::NAME);
        static::assertCount(3, $results);

        foreach ($results as $result) {
            static::assertEquals(FieldCommunity::NAME->value, $result->name);
        }
    }

    public function testRemoveField(): void
    {
        [$community, $field] = flush_after(function () {
            $field = DummyFieldFactory::createOne([
                'name' => FieldCommunity::NAME->value,
                Field::getPropertyName(FieldCommunity::NAME) => 'mon nom',
            ])->_real();

            return [
                DummyCommunityFactory::createOne([
                    'fields' => [$field],
                ])->_real(),
                $field,
            ];
        });

        static::assertCount(1, $community->fields);
        $community->removeField($field);
        static::assertCount(0, $community->fields);
        static::assertNull($field->place);
    }
}
