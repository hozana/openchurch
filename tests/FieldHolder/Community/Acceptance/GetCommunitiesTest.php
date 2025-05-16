<?php

namespace App\Tests\FieldHolder\Community\Acceptance;

use App\Core\Domain\Search\Helper\SearchHelperInterface;
use App\Core\Domain\Search\Service\SearchServiceInterface;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Exception\CommunityTypeNotProvidedException;
use App\FieldHolder\Community\Domain\Model\Community;
use App\Shared\Domain\Enum\SearchIndex;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\FieldHolder\Community\DummyFactory\DummyCommunityFactory;
use App\Tests\Helper\AcceptanceTestHelper;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Zenstruck\Foundry\Test\Factories;

class GetCommunitiesTest extends AcceptanceTestHelper
{
    use Factories;

    public SearchHelperInterface $searchHelper;
    public SearchServiceInterface $searchService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchHelper = static::getContainer()->get(SearchHelperInterface::class);
        $this->searchService = static::getContainer()->get(SearchServiceInterface::class);
    }

    public function testFilterByWikidataId(): void
    {
        /** @var Community[] $communities */
        $communities = DummyCommunityFactory::createMany(3);

        DummyFieldFactory::createOne([
            'name' => FieldCommunity::WIKIDATA_ID->value,
            'intVal' => 123,
            'community' => $communities[0],
        ]);
        DummyFieldFactory::createOne([
            'name' => FieldCommunity::WIKIDATA_ID->value,
            'intVal' => 456,
            'community' => $communities[1],
        ]);

        $response = self::assertResponse($this->get('/communities', querystring: [
            FieldCommunity::WIKIDATA_ID->value => 123,
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
            Field::getPropertyName(FieldCommunity::TYPE) => 'diocese',
            'community' => $communities[0],
        ]);
        DummyFieldFactory::createOne([
            'name' => FieldCommunity::TYPE->value,
            Field::getPropertyName(FieldCommunity::TYPE) => 'parish',
            'community' => $communities[1],
        ]);

        $response = self::assertResponse($this->get('/communities', querystring: [
            FieldCommunity::TYPE->value => 'parish',
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(1, $response);
        self::assertEquals($communities[1]->id, $response[0]['id']);
    }

    public function testFilterByName(): void
    {
        $this->searchHelper->deleteIndex(SearchIndex::PARISH);
        $this->searchHelper->createIndex(SearchIndex::PARISH);
        $this->searchHelper->putMapping(SearchIndex::PARISH);

        $community1 = DummyCommunityFactory::createOne([
            'fields' => [
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::TYPE->value,
                    Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::PARISH->value,
                ]),
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::NAME->value,
                    Field::getPropertyName(FieldCommunity::NAME) => 'Paroisse Saint-Domice',
                ]),
            ],
        ])->_real();
        $community2 = DummyCommunityFactory::createOne([
            'fields' => [
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::TYPE->value,
                    Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::PARISH->value,
                ]),
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::NAME->value,
                    Field::getPropertyName(FieldCommunity::NAME) => 'Paroisse Notre-Dame-du-Mont-Carmel',
                ]),
            ],
        ])->_real();
        $community3 = DummyCommunityFactory::createOne([
            'fields' => [
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::TYPE->value,
                    Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::PARISH->value,
                ]),
                DummyFieldFactory::createOne([
                    'name' => FieldCommunity::NAME->value,
                    Field::getPropertyName(FieldCommunity::NAME) => 'Paroisse Saint-Pierre-Saint-Paul-du-Marsan',
                ]),
            ],
        ])->_real();

        $this->searchHelper->bulkIndex(
            SearchIndex::PARISH,
            array_map(fn (Community $community) => $community->id->toString(), [$community1, $community2, $community3]),
            array_map(fn (Community $community) => ['parishName' => $community->getMostTrustableFieldByName(FieldCommunity::NAME)->stringVal], [$community1, $community2, $community3]),
        );
        $this->searchHelper->refresh(SearchIndex::PARISH);
        $this->em->flush();

        self::assertErrorResponse(
            $this->get('/communities', querystring: [
                FieldCommunity::NAME->value => 'carmel',
            ]),
            (new CommunityTypeNotProvidedException())->getStatus(),
            (new CommunityTypeNotProvidedException())->getDetail()
        );

        $response = self::assertResponse($this->get('/communities', querystring: [
            FieldCommunity::TYPE->value => CommunityType::PARISH->value,
            FieldCommunity::NAME->value => 'carmel',
        ]), HttpFoundationResponse::HTTP_OK);

        self::assertCount(1, $response);
        self::assertEquals($community2->id->toString(), $response[0]['id']);

        // Test with a name that is not indexed
        $response = self::assertResponse($this->get('/communities', querystring: [
            FieldCommunity::TYPE->value => CommunityType::PARISH->value,
            FieldCommunity::NAME->value => 'montpellier',
        ]), HttpFoundationResponse::HTTP_OK);
        self::assertCount(0, $response);

        // Test with a stopword
        $response = self::assertResponse($this->get('/communities', querystring: [
            FieldCommunity::TYPE->value => CommunityType::PARISH->value,
            FieldCommunity::NAME->value => 'saint',
        ]), HttpFoundationResponse::HTTP_OK);
        self::assertCount(2, $response);

        // Test alphabetical order is kept
        $response = self::assertResponse($this->get('/communities', querystring: [
            FieldCommunity::TYPE->value => CommunityType::PARISH->value,
            FieldCommunity::NAME->value => '',
        ]), HttpFoundationResponse::HTTP_OK);
        self::assertCount(3, $response);
        self::assertEquals('Paroisse Notre-Dame-du-Mont-Carmel', $response[0]['fields'][0]['value']);
        self::assertEquals('Paroisse Saint-Domice', $response[1]['fields'][0]['value']);
        self::assertEquals('Paroisse Saint-Pierre-Saint-Paul-du-Marsan', $response[2]['fields'][0]['value']);
    }
}
