<?php

namespace App\Tests\Acceptance;

use App\Agent\Domain\Model\Agent;
use App\Field\Domain\Enum\FieldPlace;
use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Model\Field;
use App\Place\Domain\Enum\PlaceType;
use App\Place\Domain\Model\Place;
use App\Tests\Factory\Model\AgentFactory;
use App\Tests\Factory\Model\PlaceFactory;
use App\Tests\Helper\AcceptanceTestHelper;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\Uid\UuidV7;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class UpdatePlaceProcessorTest extends AcceptanceTestHelper
{
    use ResetDatabase, Factories;

    public function testShouldPassWithGoodData(): void
    {
        $agent = AgentFactory::createOne();
        $place = PlaceFactory::createOne();
        
        $response = self::assertResponse($this->patch('/places', $agent->apiKey, body: [
            'id' => $place->id,
            'fields' => [
                [
                    'name' => FieldPlace::WIKIDATA_ID,
                    'value' => 484848151,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ],
            ]
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(1, $response['fields']);
        self::assertEquals($place->id->toString(), $response['id']);
        self::assertEquals($agent->id, $response['fields'][0]['agent']['id']);
        self::assertEquals(FieldPlace::WIKIDATA_ID->value, $response['fields'][0]['name']);
        self::assertEquals(484848151, $response['fields'][0]['value']);
    }

    public function testShouldThrowIfFieldNameNotValid(): void
    {
        $agent = AgentFactory::createOne();
        $place = PlaceFactory::createOne();

        self::assertResponse($this->patch('/places', $agent->apiKey, body: [
            'id' => $place->id,
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

    }

    public function testShouldThrowIfFieldValueNotValid(): void
    {
        $agent = AgentFactory::createOne();
        $place = PlaceFactory::createOne();

        self::assertResponse($this->patch('/places', $agent->apiKey, body: [
            'id' => $place->id,
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
    }

    public function testShouldThrowIfUnicityConstraintViolation(): void
    {
        // $agent = AgentFactory::createOne();
        // $place = PlaceFactory::new()
        //     ->withField([
        //         'name' => FieldPlace::WIKIDATA_ID->value,
        //         'value' => '123456'
        //     ])
        //     ->create()
        //     ;

        // TODO change me when https://github.com/zenstruck/foundry/issues/710 is fixed
        $agent = new Agent();
        $agent->name = 'toto';
        $agent->apiKey = '123456';
        $this->em->persist($agent);

        $field = new Field();
        $field->name =  FieldPlace::WIKIDATA_ID->value;
        $field->intVal = 123456;
        $field->reliability = FieldReliability::HIGH;
        $field->engine = FieldEngine::AI;
        $field->agent = $agent;
        $this->em->persist($field);

        $fieldPlace2 = new Field();
        $fieldPlace2->name =  FieldPlace::WIKIDATA_ID->value;
        $fieldPlace2->intVal = 123457;
        $fieldPlace2->reliability = FieldReliability::HIGH;
        $fieldPlace2->engine = FieldEngine::HUMAN;
        $fieldPlace2->agent = $agent;
        $this->em->persist($fieldPlace2);

        $place = new Place();
        $place->addField($field);
        $this->em->persist($place);

        $place2 = new Place();
        $place2->addField($fieldPlace2);
        $this->em->persist($place2);

        $this->em->flush();

        $response = self::assertResponse($this->patch('/places', $agent->apiKey, body: [
            'id' => $place->id,
            'fields' => [
                [
                    'name' => FieldPlace::WIKIDATA_ID,
                    'value' => 123457,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ]
            ]
        ]), HttpFoundationResponse::HTTP_BAD_REQUEST);

        self::assertEquals("Found duplicate for field wikidataId with value 123457", $response['message']);
    }

    public function testShouldThrowIfPlaceNotFound(): void
    {
        $agent = AgentFactory::createOne();

        self::assertResponse($this->patch('/places/', $agent->apiKey, body: [
            'id' => UuidV7::v7()->toString(),
            'fields' => [
                [
                    'name' => FieldPlace::CAPACITY,
                    'value' => 10,
                    "reliability" => "high",
                    "source" => "human",
                    "explanation" => "yolo",
                    "engine" => "human"
                ]
            ]
        ]), HttpFoundationResponse::HTTP_NOT_FOUND);
    }
}