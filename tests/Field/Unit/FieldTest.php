<?php

namespace App\Tests\Field\Unit;

use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldPlace;
use App\Place\Domain\Enum\PlaceType;
use App\Tests\Community\DummyFactory\DummyCommunityFactory;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\Place\DummyFactory\DummyPlaceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class FieldTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testDefineCommunityOrPlace(): void
    {
        $field = DummyFieldFactory::createOne([
            'community' => DummyCommunityFactory::createOne(),
            'name' => FieldCommunity::NAME->value,
        ]);
        $violations = $this->validator->validate($field);
        static::assertCount(0, $violations);

        $field = DummyFieldFactory::createOne([
            'community' => DummyCommunityFactory::createOne(),
            'place' => DummyPlaceFactory::createOne(),
            'name' => FieldCommunity::NAME->value,
        ]);
        $violations = $this->validator->validate($field);
        static::assertCount(1, $violations);
        static::assertEquals('Field must be attached to a community or a place, not none, not both', $violations->get(0)->getMessage());

        $field = DummyFieldFactory::createOne([
            'name' => FieldCommunity::NAME->value,
        ]);
        static::assertCount(1, $violations);
        static::assertEquals('Field must be attached to a community or a place, not none, not both', $violations->get(0)->getMessage());
    }

    public function testDefineWrongType(): void
    {
        $field = DummyFieldFactory::createOne([
            'community' => DummyCommunityFactory::createOne(),
            'name' => 'toto',
        ]);
        $violations = $this->validator->validate($field);
        static::assertCount(1, $violations);
        static::assertEquals('Field toto is not acceptable', $violations->get(0)->getMessage());

        $field = DummyFieldFactory::createOne([
            'place' => DummyPlaceFactory::createOne(),
            'name' => 'toto',
        ]);
        $violations = $this->validator->validate($field);
        static::assertCount(1, $violations);
        static::assertEquals('Field toto is not acceptable', $violations->get(0)->getMessage());
    }

    public function testNotInsertCommunitiesInReplacesField(): void
    {
        $field = DummyFieldFactory::createOne([
            'community' => DummyCommunityFactory::createOne(),
            'name' => FieldCommunity::REPLACES->value,
            'value' => DummyCommunityFactory::createOne()->_real(),
        ]);
        $violations = $this->validator->validate($field);

        static::assertCount(1, $violations);
        static::assertEquals('Field replaces expected value of type Community[]', $violations->get(0)->getMessage());

        $field = DummyFieldFactory::createOne([
            'community' => DummyCommunityFactory::createOne(),
            'name' => FieldCommunity::REPLACES->value,
            'value' => [DummyPlaceFactory::createOne()->_real()],
        ]);
        $violations = $this->validator->validate($field);
        static::assertEquals('Field replaces expected value of type Community[]', $violations->get(0)->getMessage());
    }

    public function testNotInsertPlacesInReplacesField(): void
    {
        $field = DummyFieldFactory::createOne([
            'place' => DummyPlaceFactory::createOne(),
            'name' => FieldPlace::REPLACES->value,
            'value' => DummyPlaceFactory::createOne()->_real(),
        ]);
        $violations = $this->validator->validate($field);

        static::assertCount(1, $violations);
        static::assertEquals('Field replaces expected value of type Place[]', $violations->get(0)->getMessage());

        $field = DummyFieldFactory::createOne([
            'place' => DummyPlaceFactory::createOne(),
            'name' => FieldPlace::REPLACES->value,
            'value' => [DummyCommunityFactory::createOne()->_real()],
        ]);
        $violations = $this->validator->validate($field);
        static::assertEquals('Field replaces expected value of type Place[]', $violations->get(0)->getMessage());
    }

    public function testShouldFailIfValueNotInArray(): void
    {
        $field = DummyFieldFactory::createOne([
            'place' => DummyPlaceFactory::createOne(),
            'name' => FieldPlace::TYPE->value,
            'value' => 'toto',
        ]);
        $violations = $this->validator->validate($field);

        static::assertCount(1, $violations);
        static::assertEquals(sprintf('Field type does not accept value toto (accepted values: %s)', implode(', ', array_column(PlaceType::cases(), 'value'))), $violations->get(0)->getMessage());
    }
}
