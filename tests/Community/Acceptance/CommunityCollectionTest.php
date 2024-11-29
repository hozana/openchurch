<?php

namespace App\Tests\Community\Acceptance;

use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Model\Field;
use App\Tests\Community\DummyFactory\DummyCommunityFactory;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\Helper\AcceptanceTestHelper;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CommunityCollectionTest extends AcceptanceTestHelper
{
    use ResetDatabase, Factories;

    public function testFilterByWikidataId(): void
    {
        /** @var Community[] $communities */
        $communities = DummyCommunityFactory::createMany(3);

        DummyFieldFactory::createOne([
            'name' => FieldCommunity::WIKIDATA_ID->value, 
            'intVal' => 123,
            'community' => $communities[0]
        ]);
        DummyFieldFactory::createOne([
            'name' => FieldCommunity::WIKIDATA_ID->value, 
            'intVal' => 456,
            'community' => $communities[1]
        ]);

        $response = self::assertResponse($this->get('/communities', querystring: [
            FieldCommunity::WIKIDATA_ID->value => 123
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(1, $response);
        self::assertEquals($communities[0]->id, $response[0]['id']);
    }

    public function testFilterByType(): void
    {
        /** @var Community[] $communities */
        $communities = DummyCommunityFactory::createMany(3);

        DummyFieldFactory::createOne([
            'name' => FieldCommunity::TYPE->value, 
            Field::getPropertyName(FieldCommunity::TYPE) => "diocese",
            'community' => $communities[0]
        ]);
        DummyFieldFactory::createOne([
            'name' => FieldCommunity::TYPE->value, 
            Field::getPropertyName(FieldCommunity::TYPE) => "parish",
            'community' => $communities[1]
        ]);

        $response = self::assertResponse($this->get('/communities', querystring: [
            FieldCommunity::TYPE->value => "parish"
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(1, $response);
        self::assertEquals($communities[1]->id, $response[0]['id']);
    }
}