<?php

namespace App\Tests\Acceptance;

use App\Community\Domain\Enum\CommunityEnumType;
use App\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldReliability;
use App\Tests\Factory\Model\AgentFactory;
use App\Tests\Helper\AcceptanceTestHelper;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CreateCommunityProcessorTest extends AcceptanceTestHelper
{
    use ResetDatabase, Factories;

    public function testShouldPassWithGoodData(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertEmpty($communityRepository);
        $agent = AgentFactory::createOne();
        
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
                    'value' => CommunityEnumType::PARISH,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ],
            ]
        ]), HttpFoundationResponse::HTTP_ACCEPTED);

        self::assertCount(1, $communityRepository);
        self::assertCount(2, $response['fields']);
        self::assertEquals($agent->id, $response['fields'][0]['agent']['id']);
        self::assertEquals($agent->id, $response['fields'][1]['agent']['id']);
    }

    public function testShouldThrowIfFieldNameNotValid(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertEmpty($communityRepository);
        $agent = AgentFactory::createOne();
        
        self::assertResponse($this->post('/communities', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => 'toto',
                    'value' => CommunityEnumType::PARISH,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ]
            ]
        ]), HttpFoundationResponse::HTTP_BAD_REQUEST);

        self::assertCount(0, $communityRepository);
    }

    public function testShouldThrowIfFieldValueNotValid(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertEmpty($communityRepository);
        $agent = AgentFactory::createOne();
        
        self::assertResponse($this->post('/communities', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldCommunity::TYPE,
                    'value' => 123456,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ]
            ]
        ]), HttpFoundationResponse::HTTP_UNPROCESSABLE_ENTITY);

        self::assertCount(0, $communityRepository);
    }

    public function testShouldThrowIfWikidataIdAlreadyExists(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertEmpty($communityRepository);
        $agent = AgentFactory::createOne();
        
        self::assertResponse($this->post('/communities', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldCommunity::WIKIDATA_ID,
                    'value' => 123456,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ]
            ]
        ]), HttpFoundationResponse::HTTP_ACCEPTED);

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
                ]
            ]
        ]), HttpFoundationResponse::HTTP_BAD_REQUEST);
    }

    public function testShouldThrowIfMesseInfoIdAlreadyExists(): void
    {
        /** @var CommunityRepositoryInterface $communityRepository */
        $communityRepository = static::getContainer()->get(CommunityRepositoryInterface::class);

        self::assertEmpty($communityRepository);
        $agent = AgentFactory::createOne();
        
        self::assertResponse($this->post('/communities', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldCommunity::MESSESINFO_ID,
                    'value' => "aze123456",
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ]
            ]
        ]), HttpFoundationResponse::HTTP_ACCEPTED);

        self::assertCount(1, $communityRepository);

        self::assertResponse($this->post('/communities', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldCommunity::MESSESINFO_ID,
                    'value' => "aze123456",
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ]
            ]
        ]), HttpFoundationResponse::HTTP_BAD_REQUEST);
    }
}