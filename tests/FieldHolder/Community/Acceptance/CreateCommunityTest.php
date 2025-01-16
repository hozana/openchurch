<?php

namespace App\Tests\FieldHolder\Community\Acceptance;

use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Exception\FieldEntityNotFoundException;
use App\Tests\Agent\DummyFactory\DummyAgentFactory;
use App\Tests\Helper\AcceptanceTestHelper;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

class CreateCommunityTest extends AcceptanceTestHelper
{
    use Factories;

    public function testShouldPassWithGoodData(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertCount(0, $communityRepository);
        $agent = DummyAgentFactory::createOne();

        $response = self::assertResponse($this->post('/communities', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldCommunity::WIKIDATA_ID,
                    'value' => 484848151,
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
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(1, $communityRepository);
        self::assertCount(2, $response['fields']);
        self::assertEquals($agent->id, $response['fields'][0]['agent']['id']);
        self::assertEquals($agent->id, $response['fields'][1]['agent']['id']);
    }

    public function testShouldThrowIfFieldNameNotValid(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertCount(0, $communityRepository);
        $agent = DummyAgentFactory::createOne();

        self::assertResponse($this->post('/communities', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => 'toto',
                    'value' => CommunityType::PARISH,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ],
            ],
        ]), HttpFoundationResponse::HTTP_BAD_REQUEST);

        self::assertCount(0, $communityRepository);
    }

    public function testShouldThrowIfFieldValueNotValid(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertCount(0, $communityRepository);
        $agent = DummyAgentFactory::createOne();

        self::assertResponse($this->post('/communities', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldCommunity::TYPE,
                    'value' => 123456,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ],
            ],
        ]), HttpFoundationResponse::HTTP_UNPROCESSABLE_ENTITY);

        self::assertCount(0, $communityRepository);
    }

    public function testShouldThrowIfWikidataIdAlreadyExists(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertCount(0, $communityRepository);
        $agent = DummyAgentFactory::createOne();

        self::assertResponse($this->post('/communities', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldCommunity::WIKIDATA_ID,
                    'value' => 123456,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ],
            ],
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(1, $communityRepository);

        self::assertResponse($this->post('/communities', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldCommunity::WIKIDATA_ID,
                    'value' => 123456,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ],
            ],
        ]), HttpFoundationResponse::HTTP_BAD_REQUEST);
    }

    public function testShouldThrowIfMesseInfoIdAlreadyExists(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertCount(0, $communityRepository);
        $agent = DummyAgentFactory::createOne();

        self::assertResponse($this->post('/communities', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldCommunity::MESSESINFO_ID,
                    'value' => 'aze123456',
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ],
            ],
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(1, $communityRepository);

        self::assertResponse($this->post('/communities', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldCommunity::MESSESINFO_ID,
                    'value' => 'aze123456',
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ],
            ],
        ]), HttpFoundationResponse::HTTP_BAD_REQUEST);
    }

    public function testShouldThrowIfProvidedEntityNotFound(): void
    {
        $agent = DummyAgentFactory::createOne();
        $id = Uuid::v7();

        self::assertErrorResponse(
            $this->post('/communities', $agent->apiKey, body: [
                'fields' => [
                    [
                        'name' => FieldCommunity::PARENT_COMMUNITY_ID,
                        'value' => $id,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ]),
            (new FieldEntityNotFoundException($id))->getStatus(),
            (new FieldEntityNotFoundException($id))->getDetail(),
        );
    }

    public function testShouldThrowIfProvidedEntitiesNotFound(): void
    {
        $agent = DummyAgentFactory::createOne();
        $ids = [Uuid::v7(), Uuid::v7()];

        self::assertErrorResponse(
            $this->post('/communities', $agent->apiKey, body: [
                'fields' => [
                    [
                        'name' => FieldCommunity::REPLACES,
                        'value' => $ids,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ]),
            (new FieldEntityNotFoundException($ids))->getStatus(),
            (new FieldEntityNotFoundException($ids))->getDetail(),
        );
    }
}
