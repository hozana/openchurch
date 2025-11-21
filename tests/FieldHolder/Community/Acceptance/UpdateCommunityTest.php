<?php

declare(strict_types=1);

namespace App\Tests\FieldHolder\Community\Acceptance;

use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Exception\CommunityNotFoundException;
use App\Tests\Agent\DummyFactory\DummyAgentFactory;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\FieldHolder\Community\DummyFactory\DummyCommunityFactory;
use App\Tests\Helper\AcceptanceTestHelper;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\Uid\UuidV7;
use Zenstruck\Foundry\Test\Factories;

use function Zenstruck\Foundry\Persistence\flush_after;

final class UpdateCommunityTest extends AcceptanceTestHelper
{
    use Factories;

    public function testShouldPassWithGoodData(): void
    {
        $agent = DummyAgentFactory::createOne();

        [$community, $field] = flush_after(function () use ($agent) {
            $fieldWikidata = DummyFieldFactory::createOne([
                'name' => FieldCommunity::WIKIDATA_ID->value,
                Field::getPropertyName(FieldCommunity::WIKIDATA_ID) => 484848151,
                'reliability' => FieldReliability::LOW,
                'agent' => $agent,
            ]);

            return [
                DummyCommunityFactory::createOne([
                    'fields' => [
                        $fieldWikidata,
                        DummyFieldFactory::createOne([
                            'name' => FieldCommunity::TYPE->value,
                            Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::PARISH->value,
                            'reliability' => FieldReliability::HIGH,
                            'source' => 'custom_source',
                            'explanation' => 'yolo',
                            'engine' => FieldEngine::AI,
                        ]),
                    ],
                ]),
                $fieldWikidata,
            ];
        });

        $response = self::assertResponse($this->patch("/communities/$community->id", $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldCommunity::WIKIDATA_ID,
                    'value' => 111222333,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yoloV2',
                    'engine' => FieldEngine::HUMAN,
                ],
            ],
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(2, $response['fields']);
        self::assertEquals($community->id->toString(), $response['id']);
        self::assertEquals($agent->id, $response['fields'][0]['agent']['id']);
        self::assertEquals(FieldCommunity::WIKIDATA_ID->value, $response['fields'][0]['name']);
        self::assertEquals($field->getValue(), $response['fields'][0]['value']);
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
                ],
            ],
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
                ],
            ],
        ]), HttpFoundationResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testShouldThrowIfUnicityConstraintViolation(): void
    {
        $agent = DummyAgentFactory::createOne();
        $community = flush_after(fn () => DummyCommunityFactory::createOne([
            'fields' => [
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::WIKIDATA_ID->value,
                    Field::getPropertyName(FieldCommunity::WIKIDATA_ID) => 123456,
                    'reliability' => FieldReliability::HIGH,
                    'engine' => FieldEngine::AI,
                    'agent' => $agent,
                ]),
            ],
        ]));

        flush_after(fn () => DummyCommunityFactory::createOne([
            'fields' => [
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::WIKIDATA_ID->value,
                    Field::getPropertyName(FieldCommunity::WIKIDATA_ID) => 123457,
                    'reliability' => FieldReliability::HIGH,
                    'engine' => FieldEngine::AI,
                    'agent' => $agent,
                ]),
            ],
        ]));

        $response = self::assertResponse($this->patch("/communities/$community->id", $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldCommunity::WIKIDATA_ID,
                    'value' => 123457,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yolo',
                    'engine' => FieldEngine::AI,
                ],
            ],
        ]), HttpFoundationResponse::HTTP_BAD_REQUEST);

        self::assertEquals('Found duplicate for field wikidataId with value 123457', $response['detail']);
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
                    'reliability' => 'high',
                    'source' => 'human',
                    'explanation' => 'yolo',
                    'engine' => 'human',
                ],
            ],
        ]),
            new CommunityNotFoundException($id)->getStatus(),
            new CommunityNotFoundException($id)->getDetail(),
        );
    }
}
