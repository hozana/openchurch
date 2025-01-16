<?php

namespace App\Tests\FieldHolder\Place\Unit;

use App\Field\Domain\Enum\FieldPlace;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Place\Domain\Enum\PlaceState;
use App\Tests\Agent\DummyFactory\DummyAgentFactory;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\FieldHolder\Place\DummyFactory\DummyPlaceFactory;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

use function Zenstruck\Foundry\Persistence\flush_after;

class PlaceUnitTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testDeletionReasonNotSet(): void
    {
        $place = DummyPlaceFactory::new()->withoutPersisting()->create([
            'fields' => [
                DummyFieldFactory::new()->withoutPersisting()->create([
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
        $place = DummyPlaceFactory::new()->withoutPersisting()->create([
            'fields' => [
                DummyFieldFactory::new()->withoutPersisting()->create([
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
            $field = DummyFieldFactory::new()->withoutPersisting()->create([
                'name' => FieldPlace::MESSESINFO_ID->value,
                Field::getPropertyName(FieldPlace::MESSESINFO_ID) => 123456,
            ]);

            return [
                DummyPlaceFactory::new()->withoutPersisting()->create([
                    'fields' => [
                        $field,
                        DummyFieldFactory::new()->withoutPersisting()->create([
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
        $agent = DummyAgentFactory::new()->withoutPersisting()->create();
        $place = flush_after(fn () => DummyPlaceFactory::new()->withoutPersisting()->create([
            'fields' => [
                DummyFieldFactory::new()->withoutPersisting()->create([
                    'name' => FieldPlace::NAME->value,
                    Field::getPropertyName(FieldPlace::NAME) => 'mon nom',
                ]),
                DummyFieldFactory::new()->withoutPersisting()->create([
                    'name' => FieldPlace::MESSESINFO_ID->value,
                    Field::getPropertyName(FieldPlace::MESSESINFO_ID) => 789456,
                    'agent' => $agent,
                ]),
                DummyFieldFactory::new()->withoutPersisting()->create([
                    'name' => FieldPlace::MESSESINFO_ID->value,
                    Field::getPropertyName(FieldPlace::MESSESINFO_ID) => 123456,
                    'agent' => DummyAgentFactory::new()->withoutPersisting()->create(),
                ]),
            ],
        ]),
        )->_real();

        $result = $place->getFieldByNameAndAgent(FieldPlace::MESSESINFO_ID, $agent->_real());
        static::assertEquals($result->getValue(), 789456);
    }

    public function testAddField(): void
    {
        $place = DummyPlaceFactory::new()->withoutPersisting()->create()->_real();
        $field = DummyFieldFactory::new()->withoutPersisting()->create([
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
            $field = DummyFieldFactory::new()->withoutPersisting()->create([
                'name' => FieldPlace::NAME->value,
                Field::getPropertyName(FieldPlace::NAME) => 'mon nom',
            ])->_real();

            return [
                DummyPlaceFactory::new()->withoutPersisting()->create([
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
