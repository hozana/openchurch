<?php

namespace App\Tests\Acceptance;

use App\Place\Domain\Enum\PlaceEnumType;
use App\Place\Domain\Repository\PlaceRepositoryInterface;
use App\Field\Domain\Enum\FieldPlace;
use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldReliability;
use App\Place\Domain\Enum\PlaceType;
use App\Tests\Factory\Model\AgentFactory;
use App\Tests\Helper\AcceptanceTestHelper;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CreatePlaceProcessorTest extends AcceptanceTestHelper
{
    use ResetDatabase, Factories;

    public function testShouldPassWithGoodData(): void
    {
        /** @var PlaceRepositoryInterface $placeRepository */
        $placeRepository = static::getContainer()->get(PlaceRepositoryInterface::class);

        self::assertEmpty($placeRepository);
        $agent = AgentFactory::createOne();
        
        $response = self::assertResponse($this->post('/places', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldPlace::WIKIDATA_ID,
                    'value' => 484848151,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ],
                [
                    'name' => FieldPlace::TYPE,
                    'value' => PlaceType::CHAPEL,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ],
            ]
        ]), HttpFoundationResponse::HTTP_ACCEPTED);

        self::assertCount(1, $placeRepository);
        self::assertCount(2, $response['fields']);
        self::assertEquals($agent->id, $response['fields'][0]['agent']['id']);
        self::assertEquals($agent->id, $response['fields'][1]['agent']['id']);
    }

    public function testShouldThrowIfFieldNameNotValid(): void
    {
        /** @var PlaceRepositoryInterface $placeRepository */
        $placeRepository = static::getContainer()->get(PlaceRepositoryInterface::class);

        self::assertEmpty($placeRepository);
        $agent = AgentFactory::createOne();
        
        self::assertResponse($this->post('/places', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => 'toto',
                    'value' => PlaceType::CHAPEL,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ]
            ]
        ]), HttpFoundationResponse::HTTP_BAD_REQUEST);

        self::assertCount(0, $placeRepository);
    }

    public function testShouldThrowIfFieldValueNotValid(): void
    {
        /** @var PlaceRepositoryInterface $placeRepository */
        $placeRepository = static::getContainer()->get(PlaceRepositoryInterface::class);

        self::assertEmpty($placeRepository);
        $agent = AgentFactory::createOne();
        
        self::assertResponse($this->post('/places', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldPlace::TYPE,
                    'value' => 123456,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ]
            ]
        ]), HttpFoundationResponse::HTTP_UNPROCESSABLE_ENTITY);

        self::assertCount(0, $placeRepository);
    }

    public function testShouldThrowIfWikidataIdAlreadyExists(): void
    {
        /** @var PlaceRepositoryInterface $placeRepository */
        $placeRepository = static::getContainer()->get(PlaceRepositoryInterface::class);

        self::assertEmpty($placeRepository);
        $agent = AgentFactory::createOne();
        
        self::assertResponse($this->post('/places', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldPlace::WIKIDATA_ID,
                    'value' => 123456,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ]
            ]
        ]), HttpFoundationResponse::HTTP_ACCEPTED);

        self::assertCount(1, $placeRepository);

        self::assertResponse($this->post('/places', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldPlace::WIKIDATA_ID,
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
        /** @var PlaceRepositoryInterface $placeRepository */
        $placeRepository = static::getContainer()->get(PlaceRepositoryInterface::class);

        self::assertEmpty($placeRepository);
        $agent = AgentFactory::createOne();
        
        self::assertResponse($this->post('/places', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldPlace::MESSESINFO_ID,
                    'value' => "aze123456",
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ]
            ]
        ]), HttpFoundationResponse::HTTP_ACCEPTED);

        self::assertCount(1, $placeRepository);

        self::assertResponse($this->post('/places', $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldPlace::MESSESINFO_ID,
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