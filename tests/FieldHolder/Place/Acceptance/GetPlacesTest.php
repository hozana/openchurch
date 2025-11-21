<?php

declare(strict_types=1);

namespace App\Tests\FieldHolder\Place\Acceptance;

use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldPlace;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\FieldHolder\Community\DummyFactory\DummyCommunityFactory;
use App\Tests\FieldHolder\Place\DummyFactory\DummyPlaceFactory;
use App\Tests\Helper\AcceptanceTestHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Override;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Zenstruck\Foundry\Test\Factories;

final class GetPlacesTest extends AcceptanceTestHelper
{
    use Factories;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testFilterByParentCommunityId(): void
    {
        [$community1, $community2] = DummyCommunityFactory::createMany(2,
            [
                'fields' => [
                    DummyFieldFactory::createOne([
                        'name' => FieldCommunity::TYPE->value,
                        Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::PARISH->value,
                    ]),
                ],
            ]
        );

        $church1 = DummyPlaceFactory::createOne([
            'fields' => [
                DummyFieldFactory::createOne([
                    'name' => FieldPlace::PARENT_COMMUNITIES->value,
                    Field::getPropertyName(FieldPlace::PARENT_COMMUNITIES) => new ArrayCollection([$community1]),
                ]),
            ],
        ]);

        $church2 = DummyPlaceFactory::createOne([
            'fields' => [
                DummyFieldFactory::createOne([
                    'name' => FieldPlace::PARENT_COMMUNITIES->value,
                    Field::getPropertyName(FieldPlace::PARENT_COMMUNITIES) => new ArrayCollection([$community1]),
                ]),
            ],
        ]);

        $church3 = DummyPlaceFactory::createOne([
            'fields' => [
                DummyFieldFactory::createOne([
                    'name' => FieldPlace::PARENT_COMMUNITIES->value,
                    Field::getPropertyName(FieldPlace::PARENT_COMMUNITIES) => new ArrayCollection([$community2]),
                ]),
            ],
        ]);

        $church4 = DummyPlaceFactory::createOne([
            'fields' => [
                DummyFieldFactory::createOne([
                    'name' => FieldPlace::PARENT_COMMUNITIES->value,
                    Field::getPropertyName(FieldPlace::PARENT_COMMUNITIES) => new ArrayCollection([$community2]),
                ]),
            ],
        ]);

        $response = self::assertResponse($this->get('/places', querystring: [
            FieldCommunity::PARENT_COMMUNITY_ID->value => $community1->id->toString(),
        ]), HttpFoundationResponse::HTTP_OK);
        $churchIds = array_map(fn (array $church) => $church['id'], $response);
        self::assertCount(2, $churchIds);
        self::assertContains($church1->id->toString(), $churchIds);
        self::assertContains($church2->id->toString(), $churchIds);

        $response = self::assertResponse($this->get('/places', querystring: [
            FieldCommunity::PARENT_COMMUNITY_ID->value => $community2->id->toString(),
        ]), HttpFoundationResponse::HTTP_OK);
        $churchIds = array_map(fn (array $church) => $church['id'], $response);
        self::assertCount(2, $churchIds);
        self::assertContains($church3->id->toString(), $churchIds);
        self::assertContains($church4->id->toString(), $churchIds);
    }

    public function testShouldErrorIfParentCommunityIdNotAUuid(): void
    {
        self::assertErrorResponse(
            $this->get('/places', querystring: [
                FieldCommunity::PARENT_COMMUNITY_ID->value => 123,
            ]),
            HttpFoundationResponse::HTTP_BAD_REQUEST,
            sprintf('provided parentCommunityId %s is not a valid uuid', 123)
        );
    }
}
