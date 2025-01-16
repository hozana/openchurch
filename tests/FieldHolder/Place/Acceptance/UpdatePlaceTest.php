<?php

namespace App\Tests\FieldHolder\Place\Acceptance;

use App\Agent\Domain\Model\Agent;
use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldPlace;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Exception\FieldInvalidNameException;
use App\Field\Domain\Exception\FieldUnicityViolationException;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Place\Domain\Enum\PlaceType;
use App\FieldHolder\Place\Domain\Exception\PlaceNotFoundException;
use App\FieldHolder\Place\Domain\Model\Place;
use App\Tests\Agent\DummyFactory\DummyAgentFactory;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\Helper\AcceptanceTestHelper;
use App\Tests\FieldHolder\Place\DummyFactory\DummyPlaceFactory;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\Uid\UuidV7;
use Zenstruck\Foundry\Test\Factories;

use function Zenstruck\Foundry\Persistence\flush_after;

class UpdatePlaceTest extends AcceptanceTestHelper
{
    use Factories;

    public function testShouldPassWithGoodData(): void
    {
        $agent = DummyAgentFactory::createOne();

        [$place, $field] = flush_after(function () use ($agent) {
            $fieldWikidata = DummyFieldFactory::createOne([
                'name' => FieldPlace::WIKIDATA_ID->value,
                Field::getPropertyName(FieldPlace::WIKIDATA_ID) => 484848151,
                'reliability' => FieldReliability::LOW,
                'agent' => $agent,
            ]);

            return [
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
                        ]),
                    ],
                ])->_real(),
                $fieldWikidata,
            ];
        });

        $response = self::assertResponse($this->patch("/places/$place->id", $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldPlace::WIKIDATA_ID,
                    'value' => 111222333,
                    'reliability' => FieldReliability::HIGH,
                    'source' => 'custom_source',
                    'explanation' => 'yoloV2',
                    'engine' => FieldEngine::HUMAN,
                ],
            ],
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(2, $response['fields']);
        self::assertEquals($place->id->toString(), $response['id']);
        self::assertEquals($agent->id, $response['fields'][0]['agent']['id']);
        self::assertEquals(FieldPlace::WIKIDATA_ID->value, $response['fields'][0]['name']);
        self::assertEquals($field->getValue(), $response['fields'][0]['value']);
    }

    public function testShouldThrowIfFieldNameNotValid(): void
    {
        $agent = DummyAgentFactory::createOne();
        $place = DummyPlaceFactory::createOne();

        self::assertErrorResponse(
            $this->patch('/places/'.$place->id->toString(), $agent->apiKey, body: [
                'fields' => [
                    [
                        'name' => 'toto',
                        'value' => PlaceType::CHAPEL,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ]),
            (new FieldInvalidNameException('toto'))->getStatus(),
            (new FieldInvalidNameException('toto'))->getDetail()
        );
    }

    public function testShouldThrowIfFieldValueNotValid(): void
    {
        $agent = DummyAgentFactory::createOne();
        $place = DummyPlaceFactory::createOne();

        self::assertResponse($this->patch('/places/'.$place->id->toString(), $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldPlace::TYPE,
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
        // $agent = DummyAgentFactory::createOne();
        // $place = DummyPlaceFactory::new()
        //     ->withField([
        //         'name' => FieldPlace::WIKIDATA_ID->value,
        //         'value' => '123456'
        //     ])
        //     ->create()
        //     ->_real();

        // TODO change me when https://github.com/zenstruck/foundry/issues/710 is fixed
        $agent = new Agent();
        $agent->name = 'toto';
        $agent->apiKey = '123456';
        $this->em->persist($agent);

        $field = new Field();
        $field->name = FieldPlace::WIKIDATA_ID->value;
        $field->intVal = 123456;
        $field->reliability = FieldReliability::HIGH;
        $field->engine = FieldEngine::AI;
        $field->agent = $agent;
        $this->em->persist($field);

        $fieldPlace2 = new Field();
        $fieldPlace2->name = FieldPlace::WIKIDATA_ID->value;
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

        $response = self::assertErrorResponse(
            $this->patch('/places/'.$place->id->toString(), $agent->apiKey, body: [
                'fields' => [
                    [
                        'name' => FieldPlace::WIKIDATA_ID,
                        'value' => 123457,
                        'reliability' => FieldReliability::HIGH,
                        'source' => 'custom_source',
                        'explanation' => 'yolo',
                        'engine' => FieldEngine::AI,
                    ],
                ],
            ]),
            (new FieldUnicityViolationException(FieldPlace::WIKIDATA_ID->value, 123457))->getStatus(),
            (new FieldUnicityViolationException(FieldPlace::WIKIDATA_ID->value, 123457))->getDetail(),
        );
    }

    public function testShouldThrowIfPlaceNotFound(): void
    {
        $agent = DummyAgentFactory::createOne();
        $id = UuidV7::v7();

        self::assertErrorResponse($this->patch("/places/$id", $agent->apiKey, body: [
            'fields' => [
                [
                    'name' => FieldPlace::CAPACITY,
                    'value' => 10,
                    'reliability' => 'high',
                    'source' => 'human',
                    'explanation' => 'yolo',
                    'engine' => 'human',
                ],
            ],
        ]),
            404,
            (new PlaceNotFoundException($id))->getDetail(),
        );
    }
}
