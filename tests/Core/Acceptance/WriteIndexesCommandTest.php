<?php

namespace App\Tests\Core\Acceptance;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Core\Domain\Search\Helper\SearchHelperInterface;
use App\Core\Domain\Search\Service\SearchServiceInterface;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\Shared\Domain\Enum\SearchIndex;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\FieldHolder\Community\DummyFactory\DummyCommunityFactory;
use App\Tests\Helper\AcceptanceTestHelper;
use Zenstruck\Foundry\Test\Factories;

class WriteIndexesCommandTest extends AcceptanceTestHelper
{
    use Factories;

    protected Client $client;
    private SearchHelperInterface $searchHelper;
    private SearchServiceInterface $searchService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchHelper = static::getContainer()->get(SearchHelperInterface::class);
        $this->searchService = static::getContainer()->get(SearchServiceInterface::class);
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
                Field::getPropertyName(FieldCommunity::PARENT_COMMUNITY_ID) => $diocese2->_real(),
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
                Field::getPropertyName(FieldCommunity::PARENT_COMMUNITY_ID) => $diocese1->_real(),
            ]),
        ],
        ]);
        $this->em->flush();
        $this->runCommand('app:write:indexes');

        $this->searchHelper->refresh(SearchIndex::PARISH);
        $this->searchHelper->refresh(SearchIndex::DIOCESE);

        $indexedParishes = $this->searchService->allParishes();
        static::assertEquals([$parish1->id->toString(), $parish2->id->toString()], $indexedParishes);

        $indexedDioceses = $this->searchService->allDioceses();
        static::assertEquals([$diocese1->id->toString(), $diocese2->id->toString()], $indexedDioceses);

        $rawResults = $this->searchHelper->all(SearchIndex::PARISH, 100, 0);
        static::assertEquals([
            [
                '_index' => 'parish',
                '_id' => $parish1->id->toString(),
                '_score' => 1.0,
                '_source' => [
                    'parishName' => 'Paroisse Saint-Pierre-Saint-Paul-du-Marsan',
                    'dioceseName' => "Diocèse d'Aire de Dax",
                ],
            ],
            [
                '_index' => 'parish',
                '_id' => $parish2->id->toString(),
                '_score' => 1.0,
                '_source' => [
                    'parishName' => 'Ensemble Paroissial de Bagnols-sur-Cèze',
                    'dioceseName' => 'Diocèse de Nîmes',
                ],
            ],
        ],
            $rawResults['hits']['hits']);
    }
}
