<?php

namespace App\Tests\Community\Unit;

use App\Community\Domain\Enum\CommunityState;
use App\Community\Domain\Model\Community;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldReliability;
use App\Tests\Community\DummyFactory\DummyCommunityFactory;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CommunityTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    private Community $community;
    private ExecutionContextInterface $context;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->community = new Community();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testStateDeletedWithoutReason(): void
    {
        $community = DummyCommunityFactory::createOne(['fields' => [
            DummyFieldFactory::createOne([
                    'name' => FieldCommunity::STATE->value, 'stringVal' => CommunityState::DELETED->value,
                ])
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
                ])
        ]]);

        $violations = $this->validator->validate($community);
        static::assertCount(1, $violations);
        static::assertEquals("Country code '-1' is not valid.", $violations->get(0)->getMessage());
    }

    public function testGetMostTrustableFieldByName() {
        $community = DummyCommunityFactory::createOne(['fields' => 
            [
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
            ]
        ]);

        $result = $community->getMostTrustableFieldByName(FieldCommunity::NAME);
        static::assertEquals('high reliability name', $result->getValue());

        $community = DummyCommunityFactory::createOne();
        $result = $community->getMostTrustableFieldByName(FieldCommunity::NAME);
        static::assertEmpty($result);
    }

    public function testGetFieldsByName() {
        $community = DummyCommunityFactory::createOne(['fields' => 
            [
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
            ]
        ]);

        $results = $community->getFieldsByName(FieldCommunity::NAME);
        static::assertCount(3, $results);

        foreach ($results as $result) {
            static::assertEquals(FieldCommunity::NAME->value, $result->name);
        }
    }
}