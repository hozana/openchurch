<?php

namespace App\Tests\Community\Acceptance;

use App\Agent\Domain\Model\Agent;
use App\Community\Domain\Enum\CommunityType;
use App\Community\Domain\Exception\CommunityNotFoundException;
use App\Community\Domain\Model\Community;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Model\Field;
use App\Tests\Agent\DummyFactory\DummyAgentFactory;
use App\Tests\Community\DummyFactory\DummyCommunityFactory;
use App\Tests\Helper\AcceptanceTestHelper;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\Uid\UuidV7;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class UpdateCommunityTest extends AcceptanceTestHelper
{
    use ResetDatabase, Factories;

    public function testShouldPassWithGoodData(): void
    {
        $agent = DummyAgentFactory::createOne();
        $community = DummyCommunityFactory::createOne();
        
        $response = self::assertResponse($this->patch("/communities/$community->id", $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldCommunity::WIKIDATA_ID,
                    'value' => 484848151,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ],
            ]
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(1, $response['fields']);
        self::assertEquals($community->id->toString(), $response['id']);
        self::assertEquals($agent->id, $response['fields'][0]['agent']['id']);
        self::assertEquals(FieldCommunity::WIKIDATA_ID->value, $response['fields'][0]['name']);
        self::assertEquals(484848151, $response['fields'][0]['value']);
    }

    public function testShouldThrowIfFieldNameNotValid(): void
    {
        $agent = DummyAgentFactory::createOne();
        $community = DummyCommunityFactory::createOne();

        self::assertResponse($this->patch("/communities/$community->id", $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => 'toto',
                    'value' => CommunityType::DIOCESE,
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
        $agent = DummyAgentFactory::createOne();
        $community = DummyCommunityFactory::createOne();

        self::assertResponse($this->patch("/communities/$community->id", $agent->apiKey, body: [
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
    }

    public function testShouldThrowIfUnicityConstraintViolation(): void
    {
        // TODO change me when https://github.com/zenstruck/foundry/issues/710 is fixed
        $agent = new Agent();
        $agent->name = 'toto';
        $agent->apiKey = '123456';
        $this->em->persist($agent);

        $field = new Field();
        $field->name =  FieldCommunity::WIKIDATA_ID->value;
        $field->intVal = 123456;
        $field->reliability = FieldReliability::HIGH;
        $field->engine = FieldEngine::AI;
        $field->agent = $agent;
        $this->em->persist($field);

        $FieldCommunity2 = new Field();
        $FieldCommunity2->name =  FieldCommunity::WIKIDATA_ID->value;
        $FieldCommunity2->intVal = 123457;
        $FieldCommunity2->reliability = FieldReliability::HIGH;
        $FieldCommunity2->engine = FieldEngine::HUMAN;
        $FieldCommunity2->agent = $agent;
        $this->em->persist($FieldCommunity2);

        $community = new Community();
        $community->addField($field);
        $this->em->persist($community);

        $community2 = new Community();
        $community2->addField($FieldCommunity2);
        $this->em->persist($community2);

        $this->em->flush();

        $response = self::assertResponse($this->patch("/communities/$community->id", $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldCommunity::WIKIDATA_ID,
                    'value' => 123457,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ]
            ]
        ]), HttpFoundationResponse::HTTP_BAD_REQUEST);

        self::assertEquals("Found duplicate for field wikidataId with value 123457", $response['detail']);
    }

    public function testShouldThrowIfCommunityNotFound(): void
    {
        $agent = DummyAgentFactory::createOne();
        $id = UuidV7::v7();

        self::assertErrorResponse($this->patch("/communities/$id", $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldCommunity::CONTACT_CITY,
                    'value' => 10,
                    "reliability" => "high",
                    "source" => "human",
                    "explanation" => "yolo",
                    "engine" => "human"
                ]
            ]
            ]),
            HttpFoundationResponse::HTTP_NOT_FOUND,
            (new CommunityNotFoundException($id))->getDetail(),
        );
    }
}