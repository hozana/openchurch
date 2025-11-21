<?php

declare(strict_types=1);

namespace App\Tests\FieldHolder\Community\Acceptance;

use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Service\SearchHelperInterface;
use App\FieldHolder\Community\Domain\Service\SearchServiceInterface;
use App\Shared\Domain\Enum\SearchIndex;
use App\Tests\Field\DummyFactory\DummyFieldFactory;
use App\Tests\FieldHolder\Community\DummyFactory\DummyCommunityFactory;
use App\Tests\Helper\AcceptanceTestHelper;
use Override;
use Zenstruck\Foundry\Test\Factories;

final class IndexCommunitiesCommandTest extends AcceptanceTestHelper
{
    use Factories;
    private SearchHelperInterface $searchHelper;
    private SearchServiceInterface $searchService;

    #[Override]
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
                    'parishName' => 'Ensemble Paroissial de Bagnols-sur-Cèze',
                    'dioceseId' => $diocese1->id->toString(),
                    'dioceseName' => 'Diocèse de Nîmes',
                ],
            ],
        ],
            $rawResults['hits']['hits']);
    }
}
