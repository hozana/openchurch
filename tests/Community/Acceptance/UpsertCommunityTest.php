<?php

namespace App\Community\Acceptance;

use App\Community\Domain\Enum\CommunityType;
use App\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Model\Field;
use App\Tests\Agent\DummyFactory\DummyAgentFactory;
use App\Tests\Community\DummyFactory\DummyCommunityFactory;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
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
                            'name' => FieldCommunity::TYPE->value,
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
                        'value' => "12345",
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
                ],
            ],
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(2, $communityRepository);
        self::assertEquals($response, [
            9999999 => "Updated",
            8888888 => "Inserted",
        ]);
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

        self::assertCount(0, $communityRepository);
        self::assertEquals($response, [
            123456 => "Field toto: invalid field name",
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
            123456 => 'value: Field type does not accept value toto (accepted values: parish, parishGroup, deanery, sanctuary, religiousCommunity, congregation, diocese)'
        ]);
    }

    public function testShouldErrorIfWikidataIdNotProvided(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertCount(0, $communityRepository);
        $agent = DummyAgentFactory::createOne();

        $response = self::assertErrorResponse($this->put('/communities/upsert', $agent->apiKey, body: [
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
                            Field::getPropertyName(FieldCommunity::MESSESINFO_ID) => "messeInfoId",
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
                        'value' => "messeInfoId",
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
            8888888 => "Found duplicate for field messesInfoId with value messeInfoId"
        ]);
    }
}
