<?php

declare(strict_types=1);

namespace App\Tests\FieldHolder\Community\Acceptance;

use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Application\CommunitySearchIndexer;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Service\SearchHelperInterface;
use App\FieldHolder\Community\Domain\Service\SearchServiceInterface;
use App\Shared\Domain\Enum\SearchIndex;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\FieldHolder\Community\DummyFactory\DummyCommunityFactory;
use App\Tests\Helper\AcceptanceTestHelper;
use Zenstruck\Foundry\Test\Factories;

final class IndexCommunitiesCommandTest extends AcceptanceTestHelper
{
    use Factories;

    private SearchHelperInterface $searchHelper;

    private SearchServiceInterface $searchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchHelper = self::getContainer()->get(SearchHelperInterface::class);
        $this->searchService = self::getContainer()->get(SearchServiceInterface::class);
    }

    public function testExecute(): void
    {
        $diocese1 = DummyCommunityFactory::createOne(['fields' => [
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::TYPE->value,
                Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::DIOCESE->value,
            ]),
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::NAME->value,
                Field::getPropertyName(FieldCommunity::NAME) => 'Diocèse de Nîmes',
            ]),
        ],
        ]);
        $diocese2 = DummyCommunityFactory::createOne(['fields' => [
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::TYPE->value,
                Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::DIOCESE->value,
            ]),
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::NAME->value,
                Field::getPropertyName(FieldCommunity::NAME) => "Diocèse d'Aire de Dax",
            ]),
        ],
        ]);
        $parish1 = DummyCommunityFactory::createOne(['fields' => [
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::TYPE->value,
                Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::PARISH->value,
            ]),
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::NAME->value,
                Field::getPropertyName(FieldCommunity::NAME) => 'Paroisse Saint-Pierre-Saint-Paul-du-Marsan',
            ]),
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::PARENT_COMMUNITY_ID->value,
                Field::getPropertyName(FieldCommunity::PARENT_COMMUNITY_ID) => $diocese2,
            ]),
        ],
        ]);
        $parish2 = DummyCommunityFactory::createOne(['fields' => [
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::TYPE->value,
                Field::getPropertyName(FieldCommunity::TYPE) => CommunityType::PARISH->value,
            ]),
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::NAME->value,
                Field::getPropertyName(FieldCommunity::NAME) => 'Ensemble Paroissial de Bagnols-sur-Cèze',
            ]),
            DummyFieldFactory::createOne([
                'name' => FieldCommunity::PARENT_COMMUNITY_ID->value,
                Field::getPropertyName(FieldCommunity::PARENT_COMMUNITY_ID) => $diocese1,
            ]),
        ],
        ]);
        $this->em->flush();
        $this->runCommand('app:index:communities');

        $this->searchHelper->refresh(SearchIndex::PARISH);
        $this->searchHelper->refresh(SearchIndex::DIOCESE);

        $indexedParishes = $this->searchService->allParishes();
        self::assertEquals([$parish1->id->toString(), $parish2->id->toString()], $indexedParishes);

        $indexedDioceses = $this->searchService->allDioceses();
        self::assertEquals([$diocese1->id->toString(), $diocese2->id->toString()], $indexedDioceses);

        $rawResults = $this->searchHelper->all(SearchIndex::PARISH, 100, 0);
        self::assertEquals([
            [
                '_index' => 'parish',
                '_id' => $parish1->id->toString(),
                '_score' => 1.0,
                '_source' => [
                    'id' => $parish1->id->toString(),
                    'parishName' => 'Paroisse Saint-Pierre-Saint-Paul-du-Marsan',
                    'dioceseId' => $diocese2->id->toString(),
                    'dioceseName' => "Diocèse d'Aire de Dax",
                ],
            ],
            [
                '_index' => 'parish',
                '_id' => $parish2->id->toString(),
                '_score' => 1.0,
                '_source' => [
                    'id' => $parish2->id->toString(),
                    'parishName' => 'Ensemble Paroissial de Bagnols-sur-Cèze',
                    'dioceseId' => $diocese1->id->toString(),
                    'dioceseName' => 'Diocèse de Nîmes',
                ],
            ],
        ],
            $rawResults['hits']['hits']);
    }

    /**
     * The full indexing and the incremental one must build the same documents, from the retained values.
     */
    public function testFullAndIncrementalIndexingAgree(): void
    {
        $nimes = $this->createCommunity(['type' => CommunityType::DIOCESE->value, 'name' => 'Diocèse de Nîmes']);
        $dax = $this->createCommunity(['type' => CommunityType::DIOCESE->value, 'name' => "Diocèse d'Aire de Dax"]);
        // Some agent sees a parish, a more trustable one a deanery
        $deanery = $this->createCommunity(['type' => CommunityType::PARISH->value, 'name' => 'Doyenné de Nîmes'], [
            ['type', CommunityType::DEANERY->value],
        ]);
        // Some agent attaches the parish to Nîmes, a more trustable one to Aire et Dax
        $parish = $this->createCommunity(['type' => CommunityType::PARISH->value, 'name' => 'Paroisse du Marsan', 'parentCommunityId' => $nimes], [
            ['parentCommunityId', $dax],
        ]);
        $this->em->flush();

        $this->runCommand('app:index:communities');
        $this->searchHelper->refresh(SearchIndex::PARISH);
        self::assertNull($this->searchHelper->getDocument(SearchIndex::PARISH, $deanery->id->toString()));
        $fullDocument = $this->searchHelper->getDocument(SearchIndex::PARISH, $parish->id->toString())['_source'] ?? null;
        self::assertSame([
            'id' => $parish->id->toString(),
            'parishName' => 'Paroisse du Marsan',
            'dioceseId' => $dax->id->toString(),
            'dioceseName' => "Diocèse d'Aire de Dax",
        ], $fullDocument);

        self::getContainer()->get(CommunitySearchIndexer::class)->index($parish, $deanery);
        $this->searchHelper->refresh(SearchIndex::PARISH);
        self::assertNull($this->searchHelper->getDocument(SearchIndex::PARISH, $deanery->id->toString()));
        self::assertSame($fullDocument, $this->searchHelper->getDocument(SearchIndex::PARISH, $parish->id->toString())['_source'] ?? null);
    }

    /**
     * @param array<string, mixed>       $fields        values of a low reliability agent, indexed by field name
     * @param list<array{string, mixed}> $trustedFields values of a high reliability agent
     */
    private function createCommunity(array $fields, array $trustedFields = []): Community
    {
        $created = [];
        foreach ($fields as $name => $value) {
            $created[] = DummyFieldFactory::createOne([
                'name' => $name,
                Field::getPropertyName(FieldCommunity::from($name)) => $value,
                'reliability' => FieldReliability::LOW,
            ]);
        }
        foreach ($trustedFields as [$name, $value]) {
            $created[] = DummyFieldFactory::createOne([
                'name' => $name,
                Field::getPropertyName(FieldCommunity::from($name)) => $value,
                'reliability' => FieldReliability::HIGH,
            ]);
        }

        return DummyCommunityFactory::createOne(['fields' => $created]);
    }
}
