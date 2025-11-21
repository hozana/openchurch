<?php

declare(strict_types=1);

namespace App\Tests\FieldHolder\Community\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Community\Infrastructure\ElasticSearch\OfficialElasticSearchHelper;
use App\FieldHolder\Community\Infrastructure\ElasticSearch\OfficialElasticSearchService;
use App\Shared\Domain\Enum\SearchIndex;

final class OfficialElasticSearchServiceTest extends ApiTestCase
{
    public OfficialElasticSearchHelper $elasticHelper;
    public OfficialElasticSearchService $elasticService;

    protected function setUp(): void
    {
        parent::setUp();

        /* @var OfficialElasticSearchHelper $elasticHelper */
        $this->elasticHelper = new OfficialElasticSearchHelper($_ENV['ELASTICSEARCH_IRI']);
        $this->elasticService = new OfficialElasticSearchService(
            $this->elasticHelper,
            self::getContainer()->get(CommunityRepositoryInterface::class),
        );

        $this->elasticHelper->deleteIndex(SearchIndex::DIOCESE);
        $this->elasticHelper->createIndex(SearchIndex::DIOCESE);
        $this->elasticHelper->putMapping(SearchIndex::DIOCESE);
        $this->elasticHelper->deleteIndex(SearchIndex::PARISH);
        $this->elasticHelper->createIndex(SearchIndex::PARISH);
        $this->elasticHelper->putMapping(SearchIndex::PARISH);
    }

    public function testSimpleDioceseQuery(): void
    {
        $dioceseIds = [
            "Diocèse d'Avignon",
            "Diocèse d'Aire de Dax",
            'Diocèse de Beauvais, Noyon et Senlis',
            'Archidiocèse de Montpellier',
            'Diocèse de Montauban',
        ];

        $this->elasticHelper->bulkIndex(
            SearchIndex::DIOCESE,
            $dioceseIds,
            array_map(fn (string $id) => ['dioceseName' => $id], $dioceseIds),
        );

        $this->elasticHelper->refresh(SearchIndex::DIOCESE);
        $ids = $this->elasticService->searchDioceseIds('Montauban', 3, 0);
        self::assertSame([0 => $dioceseIds[4]], $ids);

        // check if d' is a stopword
        $ids = $this->elasticService->searchDioceseIds("d'", 3, 0);
        self::assertCount(0, $ids);
    }

    public function testSimpleParihQuery(): void
    {
        $parishes = [
            ['dioceseName' => "Diocèse d'Amiens", 'parishName' => 'Paroisse Saint-Domice'],
            ['dioceseName' => "Diocèse d'Aire et Dax", 'parishName' => 'Paroisse Notre-Dame-du-Mont-Carmel'],
            ['dioceseName' => "Diocèse d'Autun, Chalon et Mâcon", 'parishName' => 'Paroisse Saint-Joseph-Ouvrier'],
            ['dioceseName' => "Archidiocèse d'Aix-en-Provence et Arles", 'parishName' => 'Unité pastorale Saint-Michel'],
            ['dioceseName' => "Diocèse d'Ajaccio", 'parishName' => 'Paroisse de Zonza'],
            ['dioceseName' => "Diocèse d'Aire et Dax", 'parishName' => 'Paroisse Saint-Pierre-Saint-Paul-du-Marsan'],
        ];

        $this->elasticHelper->bulkIndex(
            SearchIndex::PARISH,
            array_column($parishes, 'parishName'),
            array_map(fn (array $parish) => ['parishName' => $parish['parishName'], 'dioceseName' => $parish['dioceseName']], $parishes),
        );
        $this->elasticHelper->refresh(SearchIndex::PARISH);

        $ids = $this->elasticService->searchParishIds('Joseph', null, 3, 0);
        self::assertSame([0 => $parishes[2]['parishName']], $ids);

        $ids = $this->elasticService->searchParishIds('carmeel', null, 3, 0);
        self::assertSame([0 => $parishes[1]['parishName']], $ids);

        // search by diocese name
        $ids = $this->elasticService->searchParishIds('Aire et Dax', null, 3, 0);
        self::assertSame([0 => $parishes[1]['parishName'], 1 => $parishes[5]['parishName']], $ids);

        // search by diocese name
        $ids = $this->elasticService->searchParishIds('Arles', null, 3, 0);
        self::assertSame([0 => $parishes[3]['parishName']], $ids);
    }

    public function testSearchDioceseOnSmallText(): void
    {
        $dioceses = [
            'Diocèse de Fréjus-Toulon',
            'Diocèse de France',
            'Diocèse d\'Autun, Chalon et Mâcon',
            'Archidiocèse d\'Aix-en-Provence et Arles',
            'Diocèse d\'Ajaccio',
            'Archidiocèse de Montpellier',
        ];

        $this->elasticHelper->bulkIndex(
            SearchIndex::DIOCESE,
            $dioceses,
            array_map(fn (string $dioceseName) => ['dioceseName' => $dioceseName], $dioceses),
        );
        $this->elasticHelper->refresh(SearchIndex::DIOCESE);

        self::assertSame(
            [
                'Diocèse de France',
                'Diocèse de Fréjus-Toulon',
            ],
            $this->elasticService->searchDioceseIds('fr', 6, 0)
        );
    }

    public function testSearchParish(): void
    {
        $parishes = [
            ['dioceseName' => 'Diocèse de Valence', 'parishName' => 'Paroisse Saint-Marcellin-Champagnat-en-Tricastin'],
            ['dioceseName' => 'Diocèse de Valence', 'parishName' => 'Paroisse Saint-Marcel-du-Diois'],
            ['dioceseName' => 'Diocèse de Valence', 'parishName' => 'Paroisse Notre-Dame-de-la-Valloire'],
            ['dioceseName' => 'Diocèse de Valence', 'parishName' => 'Paroisse Saint-Martin-de-la-Plaine-de-Valence'],
            ['dioceseName' => 'Archidiocèse de Paris', 'parishName' => "Paroisse Notre-Dame-d'Auteuil"],
            ['dioceseName' => 'Archidiocèse de Paris', 'parishName' => 'Paroisse Notre-Dame-d\'Espérance'],
            ['dioceseName' => 'Archidiocèse de Paris', 'parishName' => 'Paroisse Notre-Dame-de-Bonne-Nouvelle'],
            ['dioceseName' => '???', 'parishName' => "Paroisse de l'Oise"],
            ['dioceseName' => 'Diocèse d\'Arles', 'parishName' => 'Paroisse de Fos-sur-Mer'],
        ];

        $this->elasticHelper->bulkIndex(
            SearchIndex::PARISH,
            array_column($parishes, 'parishName'),
            array_map(fn (array $parish) => ['parishName' => $parish['parishName'], 'dioceseName' => $parish['dioceseName']], $parishes),
        );
        $this->elasticHelper->refresh(SearchIndex::PARISH);

        $cases = [
            'm' => [
                'Paroisse Saint-Marcellin-Champagnat-en-Tricastin',
                'Paroisse Saint-Martin-de-la-Plaine-de-Valence',
                'Paroisse Saint-Marcel-du-Diois',
                'Paroisse de Fos-sur-Mer',
            ],
            'ma' => [
                'Paroisse Saint-Marcellin-Champagnat-en-Tricastin',
                'Paroisse Saint-Martin-de-la-Plaine-de-Valence',
                'Paroisse Saint-Marcel-du-Diois',
            ],
            'mar' => [
                'Paroisse Saint-Marcellin-Champagnat-en-Tricastin',
                'Paroisse Saint-Martin-de-la-Plaine-de-Valence',
                'Paroisse Saint-Marcel-du-Diois',
            ],
            'marc' => [
                'Paroisse Saint-Marcellin-Champagnat-en-Tricastin',
                'Paroisse Saint-Marcel-du-Diois',
                'Paroisse Saint-Martin-de-la-Plaine-de-Valence',
            ],
            'marcel' => [
                'Paroisse Saint-Marcellin-Champagnat-en-Tricastin',
                'Paroisse Saint-Marcel-du-Diois',
            ],
            'marcell' => [
                'Paroisse Saint-Marcellin-Champagnat-en-Tricastin',
                'Paroisse Saint-Marcel-du-Diois',
            ],
            'marcellin' => [
                'Paroisse Saint-Marcellin-Champagnat-en-Tricastin',
            ],
            'notre' => [
                "Paroisse Notre-Dame-d'Auteuil",
                'Paroisse Notre-Dame-d\'Espérance',
                'Paroisse Notre-Dame-de-la-Valloire',
                'Paroisse Notre-Dame-de-Bonne-Nouvelle',
            ],
            'notre dame' => [
                "Paroisse Notre-Dame-d'Auteuil",
                'Paroisse Notre-Dame-d\'Espérance',
                'Paroisse Notre-Dame-de-la-Valloire',
                'Paroisse Notre-Dame-de-Bonne-Nouvelle',
            ],
            'oise' => [
                "Paroisse de l'Oise",
            ],
            'me' => [
                'Paroisse de Fos-sur-Mer',
            ],
            'sur-me' => [
                'Paroisse de Fos-sur-Mer',
            ],
        ];

        foreach ($cases as $token => $expectedValues) {
            self::assertEquals(
                $expectedValues,
                $this->elasticService->searchParishIds($token, null, 6, 0),
                $token,
            );
        }
    }

    public function testParishWithDioceseName(): void
    {
        $parishes = [
            ['dioceseName' => 'Archidiocèse de Montpellier', 'parishName' => 'Paroisse Cathédrale'],
            ['dioceseName' => "Diocèse d'Aire et Dax", 'parishName' => 'Paroisse Notre-Dame-du-Mont-Carmel'],
            ['dioceseName' => 'Archidiocèse de Montpellier', 'parishName' => 'Paroisse Sainte Thérèse'],
            ['dioceseName' => "Archidiocèse d'Aix-en-Provence et Arles", 'parishName' => 'Unité pastorale Saint-Michel'],
            ['dioceseName' => "Diocèse d'Ajaccio", 'parishName' => 'Paroisse de Zonza'],
            ['dioceseName' => 'Archidiocèse de Montpellier', 'parishName' => 'Paroisse Sainte Bernadette'],
        ];

        $this->elasticHelper->bulkIndex(
            SearchIndex::PARISH,
            array_column($parishes, 'parishName'),
            array_map(fn (array $parish) => ['parishName' => $parish['parishName'], 'dioceseName' => $parish['dioceseName']], $parishes),
        );
        $this->elasticHelper->refresh(SearchIndex::PARISH);

        $ids = $this->elasticService->searchParishIds('diocèse de', null, 10, 0);
        self::assertCount(0, $ids);

        $ids = $this->elasticService->searchParishIds('diocèse de Montpellier', null, 10, 0);
        self::assertSame([
            'Paroisse Cathédrale',
            'Paroisse Sainte Bernadette',
            'Paroisse Sainte Thérèse',
        ], $ids);

        $ids = $this->elasticService->searchParishIds('Montpellier', null, 10, 0);
        self::assertSame([
            'Paroisse Cathédrale',
            'Paroisse Sainte Bernadette',
            'Paroisse Sainte Thérèse',
        ], $ids);
    }

    public function testParishWithDioceseId(): void
    {
        $parishes = [
            ['dioceseId' => '1', 'dioceseName' => 'Archidiocèse de Montpellier', 'parishName' => 'Paroisse Cathédrale'],
            ['dioceseId' => '1', 'dioceseName' => "Diocèse d'Aire et Dax", 'parishName' => 'Paroisse Notre-Dame-du-Mont-Carmel'],
            ['dioceseId' => '1', 'dioceseName' => 'Archidiocèse de Montpellier', 'parishName' => 'Paroisse Sainte Thérèse'],
            ['dioceseId' => '2', 'dioceseName' => "Archidiocèse d'Aix-en-Provence et Arles", 'parishName' => 'Unité pastorale Saint-Michel'],
            ['dioceseId' => '2', 'dioceseName' => "Diocèse d'Ajaccio", 'parishName' => 'Paroisse de Zonza'],
            ['dioceseId' => '3', 'dioceseName' => 'Archidiocèse de Montpellier', 'parishName' => 'Paroisse Sainte Bernadette'],
        ];

        $this->elasticHelper->bulkIndex(
            SearchIndex::PARISH,
            array_column($parishes, 'parishName'),
            array_map(fn (array $parish) => ['parishName' => $parish['parishName'], 'dioceseId' => $parish['dioceseId'], 'dioceseName' => $parish['dioceseName']], $parishes),
        );
        $this->elasticHelper->refresh(SearchIndex::PARISH);

        $ids = $this->elasticService->searchParishIds('diocèse de Montpellier', '1', 10, 0);
        self::assertSame([
            'Paroisse Cathédrale',
            'Paroisse Sainte Thérèse',
        ], $ids);

        $ids = $this->elasticService->searchParishIds('Montpellier', '2', 10, 0);
        self::assertCount(0, $ids);

        $ids = $this->elasticService->searchParishIds('Montpellier', '3', 10, 0);
        self::assertSame([
            'Paroisse Sainte Bernadette',
        ], $ids);
    }

    public function testStopwords(): void
    {
        $parishes = [
            ['dioceseName' => "Diocèse d'Amiens", 'parishName' => 'Paroisse Saint-Domice'],
            ['dioceseName' => "Diocèse d'Aire et Dax", 'parishName' => 'Paroisse Notre-Dame-du-Mont-Carmel'],
            ['dioceseName' => "Diocèse d'Autun, Chalon et Mâcon", 'parishName' => 'Paroisse Saint-Joseph-Ouvrier'],
            ['dioceseName' => "Archidiocèse d'Aix-en-Provence et Arles", 'parishName' => 'Unité pastorale Saint-Michel'],
            ['dioceseName' => "Diocèse d'Ajaccio", 'parishName' => 'Paroisse de Zonza'],
            ['dioceseName' => 'Archidiocèse de Montpellier', 'parishName' => 'Paroisse Sainte Bernadette'],
            ['dioceseName' => 'Saint Diocèse de France', 'parishName' => 'Paroisse Sainte Bernadette'],
        ];

        $this->elasticHelper->bulkIndex(
            SearchIndex::DIOCESE,
            array_column($parishes, 'dioceseName'),
            array_map(fn (array $parish) => ['dioceseName' => $parish['dioceseName']], $parishes),
        );
        $this->elasticHelper->bulkIndex(
            SearchIndex::PARISH,
            array_column($parishes, 'parishName'),
            array_map(fn (array $parish) => ['parishName' => $parish['parishName'], 'dioceseName' => $parish['dioceseName']], $parishes),
        );
        $this->elasticHelper->refresh(SearchIndex::DIOCESE);
        $this->elasticHelper->refresh(SearchIndex::PARISH);

        // check if d' is a stopword
        $ids = $this->elasticService->searchParishIds("d'", null, 3, 0);
        $ids = $this->elasticService->searchDioceseIds("d'", 3, 0);
        self::assertCount(0, $ids);

        // check if diocese is a stopword
        $ids = $this->elasticService->searchParishIds('diocese', null, 3, 0);
        self::assertCount(0, $ids);
        $ids = $this->elasticService->searchDioceseIds('diocese', 3, 0);
        self::assertCount(0, $ids);

        // check if archidiocese is a stopword
        $ids = $this->elasticService->searchParishIds('archidiocèse', null, 3, 0);
        self::assertCount(0, $ids);
        $ids = $this->elasticService->searchDioceseIds('archidiocèse', 3, 0);
        self::assertCount(0, $ids);

        // check if saint is a stopword
        $ids = $this->elasticService->searchParishIds('saint', null, 3, 0);
        self::assertCount(0, $ids);
        $ids = $this->elasticService->searchDioceseIds('saint', 3, 0);
        self::assertCount(0, $ids);

        // check if sainte is a stopword
        $ids = $this->elasticService->searchParishIds('sainte', null, 3, 0);
        self::assertCount(0, $ids);
        $ids = $this->elasticService->searchDioceseIds('sainte', 3, 0);
        self::assertCount(0, $ids);
    }

    public function testParishSortingWithoutFilter(): void
    {
        $parishes = [
            'Paroisse Saint-Domice',
            'Paroisse Notre-Dame-du-Mont-Carmel',
            'Paroisse Saint-Joseph-Ouvrier',
            'Unité pastorale Saint-Michel',
            'Paroisse de Zonza',
            'Paroisse Sainte Bernadette',
        ];

        $this->elasticHelper->bulkIndex(
            SearchIndex::PARISH,
            $parishes,
            array_map(fn (string $parishName) => ['parishName' => $parishName], $parishes),
        );
        $this->elasticHelper->refresh(SearchIndex::PARISH);

        self::assertSame(
            [
                'Paroisse de Zonza',
                'Paroisse Notre-Dame-du-Mont-Carmel',
                'Paroisse Saint-Domice',
                'Paroisse Saint-Joseph-Ouvrier',
                'Paroisse Sainte Bernadette',
                'Unité pastorale Saint-Michel',
            ],
            $this->elasticService->searchParishIds('', null, 6, 0)
        );
    }

    public function testParishSortingWithFilter(): void
    {
        $parishes = [
            'Paroisse de Chateauneuf-les-Martigues/La-Mède',
            'Paroisse des Saintes-Maries-de-la-Mer',
            'Paroisse de Fos-sur-Mer',
            'Paroisse Saint-Joseph-Ouvrier',
            'Paroisse de Aos-sur-Mer',
        ];

        $this->elasticHelper->bulkIndex(
            SearchIndex::PARISH,
            $parishes,
            array_map(fn (string $parishName) => ['parishName' => $parishName], $parishes),
        );
        $this->elasticHelper->refresh(SearchIndex::PARISH);

        self::assertSame(
            [
                'Paroisse de Aos-sur-Mer',
                'Paroisse de Fos-sur-Mer',
                'Paroisse des Saintes-Maries-de-la-Mer',
                'Paroisse de Chateauneuf-les-Martigues/La-Mède',
            ],
            $this->elasticService->searchParishIds('sur-me', null, 6, 0)
        );
    }

    public function testDioceseSorting(): void
    {
        $dioceses = [
            'Diocèse d\'Amiens',
            'Diocèse d\'Aire et Dax',
            'Diocèse d\'Autun, Chalon et Mâcon',
            'Archidiocèse d\'Aix-en-Provence et Arles',
            'Diocèse d\'Ajaccio',
            'Archidiocèse de Montpellier',
        ];

        $this->elasticHelper->bulkIndex(
            SearchIndex::DIOCESE,
            $dioceses,
            array_map(fn (string $dioceseName) => ['dioceseName' => $dioceseName], $dioceses),
        );
        $this->elasticHelper->refresh(SearchIndex::DIOCESE);

        self::assertSame(
            [
                'Archidiocèse d\'Aix-en-Provence et Arles',
                'Archidiocèse de Montpellier',
                'Diocèse d\'Aire et Dax',
                'Diocèse d\'Ajaccio',
                'Diocèse d\'Amiens',
                'Diocèse d\'Autun, Chalon et Mâcon',
            ],
            $this->elasticService->searchDioceseIds('', 6, 0)
        );
    }
}
