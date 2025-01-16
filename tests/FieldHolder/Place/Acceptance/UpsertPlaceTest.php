<?php

namespace App\Tests\FieldHolder\Place\Acceptance;

use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldPlace;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Place\Domain\Enum\PlaceType;
use App\FieldHolder\Place\Domain\Repository\PlaceRepositoryInterface;
use App\Tests\Agent\DummyFactory\DummyAgentFactory;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\FieldHolder\Community\DummyFactory\DummyCommunityFactory;
use App\Tests\FieldHolder\Place\DummyFactory\DummyPlaceFactory;
use App\Tests\Helper\AcceptanceTestHelper;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Zenstruck\Foundry\Test\Factories;

use function Zenstruck\Foundry\Persistence\flush_after;

class UpsertPlaceTest extends AcceptanceTestHelper
{
    use Factories;

    public function testShouldPassWithGoodData(): void
    {
        /** @var PlaceRepositoryInterface $placeRepository */
        $placeRepository = static::getContainer()->get(PlaceRepositoryInterface::class);

        self::assertCount(0, $placeRepository);
        $agent = DummyAgentFactory::createOne();

        [$field] = flush_after(function () use ($agent) {
            $fieldWikidata = DummyFieldFactory::createOne([
                'name' => FieldPlace::WIKIDATA_ID->value,
                Field::getPropertyName(FieldPlace::WIKIDATA_ID) => 9999999,
                'reliability' => FieldReliability::LOW,
                'agent' => $agent,
            ]);

            DummyPlaceFactory::createOne([
                'fields' => [
                    $fieldWikidata->_real(),
                    DummyFieldFactory::createOne([
                        'name' => FieldPlace::TYPE->value,
                        Field::getPropertyName(FieldPlace::TYPE) => PlaceType::CHURCH->value,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                        'agent' => $agent,
                    ]),
                ],
            ])->_real();

            return [
                $fieldWikidata,
            ];
        });

        $response = self::assertResponse($this->put('/places/upsert', 'secret', body: [
            'wikidataEntities' => [
                [
                    [
                        'name' => FieldPlace::WIKIDATA_ID,
                        'value' => $field->getValue(),
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldPlace::MESSESINFO_ID,
                        'value' => '12345',
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
                [
                    [
                        'name' => FieldPlace::WIKIDATA_ID,
                        'value' => 8888888,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldPlace::TYPE,
                        'value' => PlaceType::CHURCH->value,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldPlace::WIKIDATA_UPDATED_AT,
                        'value' => (new \DateTime())->format('Y-m-d H:i:s'),
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ],
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(2, $placeRepository);
        self::assertEquals($response, [
            9999999 => 'Updated',
            8888888 => 'Inserted',
        ]);
    }

    public function testShouldPassWhenProvidingParentCommunities(): void
    {
        /** @var PlaceRepositoryInterface $placeRepository */
        $placeRepository = static::getContainer()->get(PlaceRepositoryInterface::class);

        self::assertCount(0, $placeRepository);
        $agent = DummyAgentFactory::createOne();

        [$parentCommunity, $field] = flush_after(function () use ($agent) {
            $fieldWikidata = DummyFieldFactory::createOne([
                'name' => FieldPlace::WIKIDATA_ID->value,
                Field::getPropertyName(FieldPlace::WIKIDATA_ID) => 9999999,
                'reliability' => FieldReliability::LOW,
                'agent' => $agent,
            ]);
            DummyPlaceFactory::createOne([
                'fields' => [
                    $fieldWikidata->_real(),
                    DummyFieldFactory::createOne([
                        'name' => FieldPlace::TYPE->value,
                        Field::getPropertyName(FieldPlace::TYPE) => PlaceType::CHURCH->value,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                        'agent' => $agent,
                    ]),
                ],
            ]);

            return [
                DummyCommunityFactory::createOne([
                    'fields' => [
                        $fieldWikidata->_real(),
                        DummyFieldFactory::createOne([
                            'name' => FieldPlace::TYPE->value,
                            Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::PARISH->value,
                            'reliability' => FieldReliability::HIGH,
                            'source' => 'custom_source',
                            'explanation' => 'yolo',
                            'engine' => FieldEngine::AI,
                            'agent' => $agent,
                        ]),
                    ],
                ])->_real(),
                $fieldWikidata,
            ];
        });

        $response = self::assertResponse($this->put('/places/upsert', 'secret', body: [
            'wikidataEntities' => [
                [
                    [
                        'name' => FieldPlace::PARENT_COMMUNITIES,
                        'value' => [$parentCommunity->id->toString()],
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldPlace::WIKIDATA_ID,
                        'value' => 880099,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldPlace::TYPE,
                        'value' => PlaceType::CHURCH->value,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ],
        ]), HttpFoundationResponse::HTTP_OK);

        $place = $placeRepository->withWikidataId(880099)->asCollection()[0];

        $insertedField = $place->getMostTrustableFieldByName(FieldPlace::PARENT_COMMUNITIES);
        self::assertCount(2, $placeRepository);
        self::assertEquals($response, [880099 => 'Inserted']);
        self::assertEquals($insertedField->name, FieldPlace::PARENT_COMMUNITIES->value);
        self::assertEquals($insertedField->getValue(), [$parentCommunity]);
    }

    public function testShouldPassWhenProvidingParentWikidataIds(): void
    {
        /** @var PlaceRepositoryInterface $placeRepository */
        $placeRepository = static::getContainer()->get(PlaceRepositoryInterface::class);

        self::assertCount(0, $placeRepository);
        $agent = DummyAgentFactory::createOne();

        [$parentCommunities, $fieldWikidataPlace, $fieldWikidata1, $fieldWikidata2] = flush_after(function () use ($agent) {
            $fieldWikidataPlace = DummyFieldFactory::createOne([
                'name' => FieldPlace::WIKIDATA_ID->value,
                Field::getPropertyName(FieldPlace::WIKIDATA_ID) => 00011225,
                'reliability' => FieldReliability::LOW,
                'agent' => $agent,
            ]);
            $fieldWikidata1 = DummyFieldFactory::createOne([
                'name' => FieldCommunity::WIKIDATA_ID->value,
                Field::getPropertyName(FieldCommunity::WIKIDATA_ID) => 9999999,
                'reliability' => FieldReliability::LOW,
                'agent' => $agent,
            ]);
            $fieldWikidata2 = DummyFieldFactory::createOne([
                'name' => FieldCommunity::WIKIDATA_ID->value,
                Field::getPropertyName(FieldCommunity::WIKIDATA_ID) => 9999998,
                'reliability' => FieldReliability::LOW,
                'agent' => $agent,
            ]);
            $community1 = DummyCommunityFactory::createOne([
                'fields' => [
                    $fieldWikidata1->_real(),
                    DummyFieldFactory::createOne([
                        'name' => FieldPlace::TYPE->value,
                        Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::PARISH->value,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                        'agent' => $agent,
                    ]),
                ],
            ])->_real();
            $community2 = DummyCommunityFactory::createOne([
                'fields' => [
                    $fieldWikidata2->_real(),
                    DummyFieldFactory::createOne([
                        'name' => FieldPlace::TYPE->value,
                        Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::DIOCESE->value,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                        'agent' => $agent,
                    ]),
                ],
            ])->_real();

            return [[$community1, $community2], $fieldWikidataPlace, $fieldWikidata1, $fieldWikidata2];
        });

        $response = self::assertResponse($this->put('/places/upsert', 'secret', body: [
            'wikidataEntities' => [
                [
                    [
                        'name' => FieldPlace::PARENT_WIKIDATA_IDS,
                        'value' => [$fieldWikidata1->getValue(), $fieldWikidata2->getValue()],
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldPlace::WIKIDATA_ID,
                        'value' => $fieldWikidataPlace->getValue(),
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldPlace::TYPE,
                        'value' => PlaceType::CHURCH->value,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ],
        ]), HttpFoundationResponse::HTTP_OK);

        $place = $placeRepository->withWikidataId($fieldWikidataPlace->getValue())->asCollection()[0];
        $insertedField = $place->getMostTrustableFieldByName(FieldPlace::PARENT_COMMUNITIES);
        self::assertCount(1, $placeRepository);
        self::assertEquals($response, [$fieldWikidataPlace->getValue() => 'Inserted']);
        self::assertEquals($insertedField->name, FieldPlace::PARENT_COMMUNITIES->value);
        self::assertEquals($insertedField->getValue(), $parentCommunities);
    }

    public function testShouldErrorIfFieldNameNotValid(): void
    {
        /** @var PlaceRepositoryInterface $placeRepository */
        $placeRepository = static::getContainer()->get(PlaceRepositoryInterface::class);

        self::assertCount(0, $placeRepository);
        $agent = DummyAgentFactory::createOne();

        $response = self::assertResponse($this->put('/places/upsert', $agent->apiKey, body: [
            'wikidataEntities' => [
                [
                    [
                        'name' => FieldPlace::WIKIDATA_ID,
                        'value' => 147258369,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldPlace::NAME,
                        'value' => "Je m'appelle Toto",
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
                [
                    [
                        'name' => FieldPlace::WIKIDATA_ID,
                        'value' => 123456,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => 'toto',
                        'value' => PlaceType::CHAPEL->value,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ],
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(1, $placeRepository);
        self::assertEquals($response, [
            147258369 => 'Inserted',
            123456 => 'Field toto: invalid field name',
        ]);
    }

    public function testShouldErrorIfFieldValueNotValid(): void
    {
        /** @var PlaceRepositoryInterface $placeRepository */
        $placeRepository = static::getContainer()->get(PlaceRepositoryInterface::class);

        self::assertCount(0, $placeRepository);
        $agent = DummyAgentFactory::createOne();

        $response = self::assertResponse($this->put('/places/upsert', $agent->apiKey, body: [
            'wikidataEntities' => [
                [
                    [
                        'name' => FieldPlace::WIKIDATA_ID,
                        'value' => 123456,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldPlace::TYPE,
                        'value' => 'toto',
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ],
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(0, $placeRepository);
        self::assertEquals($response, [
            123456 => sprintf('value: Field type does not accept value toto (accepted values: %s)', implode(', ', array_column(PlaceType::cases(), 'value'))),
        ]);
    }

    public function testShouldErrorIfWikidataIdNotProvided(): void
    {
        /** @var PlaceRepositoryInterface $placeRepository */
        $placeRepository = static::getContainer()->get(PlaceRepositoryInterface::class);

        self::assertCount(0, $placeRepository);
        $agent = DummyAgentFactory::createOne();

        self::assertErrorResponse($this->put('/places/upsert', $agent->apiKey, body: [
            'wikidataEntities' => [
                [
                    [
                        'name' => FieldPlace::TYPE,
                        'value' => PlaceType::CATHEDRAL->value,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ],
        ]), HttpFoundationResponse::HTTP_BAD_REQUEST, 'Field wikidataId is missing');
        self::assertCount(0, $placeRepository);
    }

    public function testShouldErrorIfMesseInfoIdAlreadyExists(): void
    {
        /** @var PlaceRepositoryInterface $placeRepository */
        $placeRepository = static::getContainer()->get(PlaceRepositoryInterface::class);

        self::assertCount(0, $placeRepository);
        $agent = DummyAgentFactory::createOne();

        [$place, $field] = flush_after(function () use ($agent) {
            $fieldWikidata = DummyFieldFactory::createOne([
                'name' => FieldPlace::WIKIDATA_ID->value,
                Field::getPropertyName(FieldPlace::WIKIDATA_ID) => 9999999,
                'reliability' => FieldReliability::LOW,
                'agent' => $agent,
            ]);

            return [
                DummyPlaceFactory::createOne([
                    'fields' => [
                        $fieldWikidata->_real(),
                        DummyFieldFactory::createOne([
                            'name' => FieldPlace::MESSESINFO_ID->value,
                            Field::getPropertyName(FieldPlace::MESSESINFO_ID) => 'messeInfoId',
                            'reliability' => FieldReliability::HIGH,
                            'source' => 'custom_source',
                            'explanation' => 'yolo',
                            'engine' => FieldEngine::AI,
                            'agent' => $agent,
                        ]),
                    ],
                ])->_real(),
                $fieldWikidata,
            ];
        });

        $response = self::assertResponse($this->put('/places/upsert', 'secret', body: [
            'wikidataEntities' => [
                [
                    [
                        'name' => FieldPlace::WIKIDATA_ID,
                        'value' => 8888888,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldPlace::MESSESINFO_ID,
                        'value' => 'messeInfoId',
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ],
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(1, $placeRepository);
        self::assertEquals($response, [
            8888888 => 'Found duplicate for field messesInfoId with value messeInfoId',
        ]);
    }
}
