<?php

namespace App\Tests\Place\Unit;

use App\Field\Domain\Enum\FieldPlace;
use App\Field\Domain\Model\Field;
use App\Place\Domain\Enum\PlaceState;
use App\Tests\Agent\DummyFactory\DummyAgentFactory;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\Place\DummyFactory\DummyPlaceFactory;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

use function Zenstruck\Foundry\Persistence\flush_after;

class PlaceTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testDeletionReasonNotSet(): void
    {
        $place = DummyPlaceFactory::createOne([
            'fields' => [
                DummyFieldFactory::createOne([
                    'name' => FieldPlace::STATE->value,
                    Field::getPropertyName(FieldPlace::STATE) => PlaceState::DELETED->value,
                ]),
            ],
        ]);
        $violations = $this->validator->validate($place);
        static::assertCount(1, $violations);
        static::assertEquals('Deletion reason is mandatory when reporting a state=deleted state.', $violations->get(0)->getMessage());
    }

    public function testWrongCountryCode(): void
    {
        $place = DummyPlaceFactory::createOne([
            'fields' => [
                DummyFieldFactory::createOne([
                    'name' => FieldPlace::COUNTRY_CODE->value,
                    Field::getPropertyName(FieldPlace::COUNTRY_CODE) => -1,
                ]),
            ],
        ]);
        $violations = $this->validator->validate($place);
        static::assertCount(1, $violations);
        static::assertEquals("Country code '-1' is not valid.", $violations->get(0)->getMessage());
    }

    public function testGetFieldsByName(): void
    {
        [$place, $field] = flush_after(function () {
            $field = DummyFieldFactory::createOne([
                'name' => FieldPlace::MESSESINFO_ID->value,
                Field::getPropertyName(FieldPlace::MESSESINFO_ID) => 123456,
            ]);

            return [
                DummyPlaceFactory::createOne([
                    'fields' => [
                        $field,
                        DummyFieldFactory::createOne([
                            'name' => FieldPlace::NAME->value,
                            Field::getPropertyName(FieldPlace::NAME) => 'mon nom',
                        ]),
                    ],
                ])->_real(),
                $field,
            ];
        });

        $result = $place->getFieldsByName(FieldPlace::MESSESINFO_ID);
        static::assertEquals(new ArrayCollection([$field->_real()]), $result);
    }

    public function testGetFieldByNameAndAgent(): void
    {
        $agent = DummyAgentFactory::createOne();
        $place = flush_after(fn () => DummyPlaceFactory::createOne([
            'fields' => [
                DummyFieldFactory::createOne([
                    'name' => FieldPlace::NAME->value,
                    Field::getPropertyName(FieldPlace::NAME) => 'mon nom',
                ]),
                DummyFieldFactory::createOne([
                    'name' => FieldPlace::MESSESINFO_ID->value,
                    Field::getPropertyName(FieldPlace::MESSESINFO_ID) => 789456,
                    'agent' => $agent,
                ]),
                DummyFieldFactory::createOne([
                    'name' => FieldPlace::MESSESINFO_ID->value,
                    Field::getPropertyName(FieldPlace::MESSESINFO_ID) => 123456,
                    'agent' => DummyAgentFactory::createOne(),
                ]),
            ],
        ]),
        )->_real();

        $result = $place->getFieldByNameAndAgent(FieldPlace::MESSESINFO_ID, $agent->_real());
        static::assertEquals($result->getValue(), 789456);
    }

    public function testAddField(): void
    {
        $place = DummyPlaceFactory::createOne()->_real();
        $field = DummyFieldFactory::createOne([
            'name' => FieldPlace::NAME->value,
            Field::getPropertyName(FieldPlace::NAME) => 'mon nom',
        ])->_real();

        static::assertCount(0, $place->fields);
        $place->addField($field);

        static::assertCount(1, $place->fields);
        static::assertEquals($field, $place->fields->first());
        static::assertEquals($place, $field->place);
    }

    public function testRemoveField(): void
    {
        [$place, $field] = flush_after(function () {
            $field = DummyFieldFactory::createOne([
                'name' => FieldPlace::NAME->value,
                Field::getPropertyName(FieldPlace::NAME) => 'mon nom',
            ])->_real();

            return [
                DummyPlaceFactory::createOne([
                    'fields' => [$field],
                ])->_real(),
                $field,
            ];
        });

        static::assertCount(1, $place->fields);
        $place->removeField($field);
        static::assertCount(0, $place->fields);
        static::assertNull($field->place);
    }
}
