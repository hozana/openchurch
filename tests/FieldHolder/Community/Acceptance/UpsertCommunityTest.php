<?php

namespace App\Tests\FieldHolder\Community\Acceptance;

use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Tests\Agent\DummyFactory\DummyAgentFactory;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\FieldHolder\Community\DummyFactory\DummyCommunityFactory;
use App\Tests\Helper\AcceptanceTestHelper;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Zenstruck\Foundry\Test\Factories;

use function Zenstruck\Foundry\Persistence\flush_after;

class UpsertCommunityTest extends AcceptanceTestHelper
{
    use Factories;

    public function testShouldPassWithGoodData(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertCount(0, $communityRepository);
        $agent = DummyAgentFactory::createOne();

        [$field] = flush_after(function () use ($agent) {
            $fieldWikidata = DummyFieldFactory::createOne([
                'name' => FieldCommunity::WIKIDATA_ID->value,
                Field::getPropertyName(FieldCommunity::WIKIDATA_ID) => 9999999,
                'reliability' => FieldReliability::LOW,
                'agent' => $agent,
            ]);

            DummyCommunityFactory::createOne([
                'fields' => [
                    $fieldWikidata->_real(),
                    DummyFieldFactory::createOne([
                        'name' => FieldCommunity::TYPE->value,
                        Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::PARISH->value,
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

        $response = self::assertResponse($this->put('/communities/upsert', 'secret', body: [
            'wikidataEntities' => [
                [
                    [
                        'name' => FieldCommunity::WIKIDATA_ID,
                        'value' => $field->getValue(),
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldCommunity::MESSESINFO_ID,
                        'value' => '12345',
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
                [
                    [
                        'name' => FieldCommunity::WIKIDATA_ID,
                        'value' => 8888888,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldCommunity::TYPE,
                        'value' => CommunityType::PARISH,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldCommunity::WIKIDATA_UPDATED_AT,
                        'value' => (new \DateTime())->format('Y-m-d H:i:s'),
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ],
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(2, $communityRepository);
        self::assertEquals($response, [
            9999999 => 'Updated',
            8888888 => 'Inserted',
        ]);
    }

    public function testShouldInsertWhenProvidingParentWikidataId(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertCount(0, $communityRepository);
        $agent = DummyAgentFactory::createOne();

        [$parentCommunity, $field] = flush_after(function () use ($agent) {
            $fieldWikidata = DummyFieldFactory::createOne([
                'name' => FieldCommunity::WIKIDATA_ID->value,
                Field::getPropertyName(FieldCommunity::WIKIDATA_ID) => 9999999,
                'reliability' => FieldReliability::LOW,
                'agent' => $agent,
            ]);

            return [
                DummyCommunityFactory::createOne([
                    'fields' => [
                        $fieldWikidata->_real(),
                        DummyFieldFactory::createOne([
                            'name' => FieldCommunity::TYPE->value,
                            Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::DIOCESE->value,
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

        $response = self::assertResponse($this->put('/communities/upsert', 'secret', body: [
            'wikidataEntities' => [
                [
                    [
                        'name' => FieldCommunity::WIKIDATA_ID,
                        'value' => 880099,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldCommunity::TYPE,
                        'value' => CommunityType::PARISH->value,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldCommunity::PARENT_WIKIDATA_ID,
                        'value' => $field->getValue(),
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ],
        ]), HttpFoundationResponse::HTTP_OK);

        $community = $communityRepository->withWikidataId(880099)->asCollection()[0];
        $insertedField = $community->getMostTrustableFieldByName(FieldCommunity::PARENT_COMMUNITY_ID);
        self::assertCount(2, $communityRepository);
        self::assertEquals($response, [880099 => 'Inserted']);
        self::assertEquals($insertedField->name, FieldCommunity::PARENT_COMMUNITY_ID->value);
        self::assertEquals($insertedField->getValue(), $parentCommunity);
    }

    public function testShouldUpdateWhenProvidingParentWikidataId(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertCount(0, $communityRepository);
        $agent = DummyAgentFactory::createOne()->_real();

        [$parentCommunity, $fieldParentWikidata, $fieldWikidata] = flush_after(function () use ($agent) {
            $fieldInitialWikidata = DummyFieldFactory::createOne([
                'name' => FieldCommunity::WIKIDATA_ID->value,
                Field::getPropertyName(FieldCommunity::WIKIDATA_ID) => 0114521,
                'reliability' => FieldReliability::LOW,
                'agent' => $agent,
            ]);
            $initialParentCommunity = DummyCommunityFactory::createOne([
                'fields' => [
                    $fieldInitialWikidata->_real(),
                    DummyFieldFactory::createOne([
                        'name' => FieldCommunity::TYPE->value,
                        Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::DIOCESE->value,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                        'agent' => $agent,
                    ]),
                ],
            ])->_real();

            $fieldWikidata = DummyFieldFactory::createOne([
                'name' => FieldCommunity::WIKIDATA_ID->value,
                Field::getPropertyName(FieldCommunity::WIKIDATA_ID) => 9999999,
                'reliability' => FieldReliability::LOW,
                'agent' => $agent,
            ]);
            $community = DummyCommunityFactory::createOne([
                'fields' => [
                    $fieldWikidata->_real(),
                    DummyFieldFactory::createOne([
                        'name' => FieldCommunity::TYPE->value,
                        Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::PARISH->value,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                        'agent' => $agent,
                    ]),
                    DummyFieldFactory::createOne([
                        'name' => FieldCommunity::PARENT_COMMUNITY_ID->value,
                        Field::getPropertyName(FieldCommunity::PARENT_COMMUNITY_ID) => $initialParentCommunity,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                        'agent' => $agent,
                    ]),
                ],
            ])->_real();

            $fieldParentWikidata = DummyFieldFactory::createOne([
                'name' => FieldCommunity::WIKIDATA_ID->value,
                Field::getPropertyName(FieldCommunity::WIKIDATA_ID) => 111111,
                'reliability' => FieldReliability::LOW,
                'agent' => $agent,
            ]);

            return [
                DummyCommunityFactory::createOne([
                    'fields' => [
                        $fieldParentWikidata->_real(),
                        DummyFieldFactory::createOne([
                            'name' => FieldCommunity::TYPE->value,
                            Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::DIOCESE->value,
                            'reliability' => FieldReliability::HIGH,
                            'source' => 'custom_source',
                            'explanation' => 'yolo',
                            'engine' => FieldEngine::AI,
                            'agent' => $agent,
                        ]),
                    ],
                ])->_real(),
                $fieldParentWikidata,
                $fieldWikidata,
                $initialParentCommunity,
            ];
        });

        $response = self::assertResponse($this->put('/communities/upsert', $agent->apiKey, body: [
            'wikidataEntities' => [
                [
                    [
                        'name' => FieldCommunity::WIKIDATA_ID,
                        'value' => $fieldWikidata->getValue(),
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldCommunity::TYPE,
                        'value' => CommunityType::PARISH->value,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldCommunity::PARENT_WIKIDATA_ID,
                        'value' => $fieldParentWikidata->getValue(),
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ],
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(3, $communityRepository);
        $updatedCommunity = $communityRepository->withWikidataId($fieldWikidata->getValue())->asCollection()[0];
        $parentCommunityFields = $updatedCommunity->getFieldsByName(FieldCommunity::PARENT_COMMUNITY_ID);
        $parentCommunityField = $updatedCommunity->getFieldByNameAndAgent(FieldCommunity::PARENT_COMMUNITY_ID, $agent);

        self::assertEquals($response, [$fieldWikidata->getValue() => 'Updated']);
        self::assertCount(1, $parentCommunityFields);
        self::assertEquals($parentCommunityField->getValue()->id, $parentCommunity->id);
    }

    public function testShouldErrorIfParentWikidataIdNotFound(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertCount(0, $communityRepository);
        $agent = DummyAgentFactory::createOne();

        [$fieldWikidata1] = flush_after(function () use ($agent) {
            $fieldWikidata1 = DummyFieldFactory::createOne([
                'name' => FieldCommunity::WIKIDATA_ID->value,
                Field::getPropertyName(FieldCommunity::WIKIDATA_ID) => 9999999,
                'reliability' => FieldReliability::LOW,
                'agent' => $agent,
            ]);
            DummyCommunityFactory::createOne([
                'fields' => [
                    $fieldWikidata1->_real(),
                    DummyFieldFactory::createOne([
                        'name' => FieldCommunity::TYPE->value,
                        Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::DIOCESE->value,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                        'agent' => $agent,
                    ]),
                ],
            ])->_real();

            return [$fieldWikidata1];
        });

        $response = self::assertResponse($this->put('/communities/upsert', 'secret', body: [
            'wikidataEntities' => [
                [
                    [
                        'name' => FieldCommunity::PARENT_WIKIDATA_ID,
                        'value' => 123,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldCommunity::WIKIDATA_ID,
                        'value' => $fieldWikidata1->getValue(),
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldCommunity::TYPE,
                        'value' => CommunityType::DIOCESE->value,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ],
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(1, $communityRepository);
        self::assertEquals($response, [$fieldWikidata1->getValue() => 'Field parentWikidataId 123 not found']);
    }

    public function testShouldErrorIfFieldNameNotValid(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertCount(0, $communityRepository);
        $agent = DummyAgentFactory::createOne();

        $response = self::assertResponse($this->put('/communities/upsert', $agent->apiKey, body: [
            'wikidataEntities' => [
                [
                    [
                        'name' => FieldCommunity::WIKIDATA_ID,
                        'value' => 147258369,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldCommunity::NAME,
                        'value' => "Je m'appelle Toto",
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
                [
                    [
                        'name' => FieldCommunity::WIKIDATA_ID,
                        'value' => 123456,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => 'toto',
                        'value' => CommunityType::PARISH,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ],
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(1, $communityRepository);
        self::assertEquals($response, [
            147258369 => 'Inserted',
            123456 => 'Field toto: invalid field name',
        ]);
    }

    public function testShouldErrorIfFieldValueNotValid(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertCount(0, $communityRepository);
        $agent = DummyAgentFactory::createOne();

        $response = self::assertResponse($this->put('/communities/upsert', $agent->apiKey, body: [
            'wikidataEntities' => [
                [
                    [
                        'name' => FieldCommunity::WIKIDATA_ID,
                        'value' => 123456,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldCommunity::TYPE,
                        'value' => 'toto',
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ],
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(0, $communityRepository);
        self::assertEquals($response, [
            123456 => sprintf('value: Field type does not accept value toto (accepted values: %s)', implode(', ', array_column(CommunityType::cases(), 'value'))),
        ]);
    }

    public function testShouldErrorIfWikidataIdNotProvided(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertCount(0, $communityRepository);
        $agent = DummyAgentFactory::createOne();

        self::assertErrorResponse($this->put('/communities/upsert', $agent->apiKey, body: [
            'wikidataEntities' => [
                [
                    [
                        'name' => FieldCommunity::TYPE,
                        'value' => CommunityType::PARISH,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ],
        ]), HttpFoundationResponse::HTTP_BAD_REQUEST, 'Field wikidataId is missing');
        self::assertCount(0, $communityRepository);
    }

    public function testShouldErrorIfMesseInfoIdAlreadyExists(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertCount(0, $communityRepository);
        $agent = DummyAgentFactory::createOne();

        [$community, $field] = flush_after(function () use ($agent) {
            $fieldWikidata = DummyFieldFactory::createOne([
                'name' => FieldCommunity::WIKIDATA_ID->value,
                Field::getPropertyName(FieldCommunity::WIKIDATA_ID) => 9999999,
                'reliability' => FieldReliability::LOW,
                'agent' => $agent,
            ]);

            return [
                DummyCommunityFactory::createOne([
                    'fields' => [
                        $fieldWikidata->_real(),
                        DummyFieldFactory::createOne([
                            'name' => FieldCommunity::MESSESINFO_ID->value,
                            Field::getPropertyName(FieldCommunity::MESSESINFO_ID) => 'messeInfoId',
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

        $response = self::assertResponse($this->put('/communities/upsert', 'secret', body: [
            'wikidataEntities' => [
                [
                    [
                        'name' => FieldCommunity::WIKIDATA_ID,
                        'value' => 8888888,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                    [
                        'name' => FieldCommunity::MESSESINFO_ID,
                        'value' => 'messeInfoId',
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ],
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(1, $communityRepository);
        self::assertEquals($response, [
            8888888 => 'Found duplicate for field messesInfoId with value messeInfoId',
        ]);
    }
}
