<?php

namespace App\Tests\Core\Integration\ElasticSearch;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Core\Infrastructure\ElasticSearch\Helper\OfficialElasticSearchHelper;
use App\Core\Infrastructure\ElasticSearch\Service\OfficialElasticSearchService;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Shared\Domain\Enum\SearchIndex;

class OfficialElasticSearchServiceTest extends ApiTestCase
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
            static::getContainer()->get(CommunityRepositoryInterface::class),
        );

        $this->elasticHelper->deleteIndex(SearchIndex::DIOCESE);
        $this->elasticHelper->createIndex(SearchIndex::DIOCESE);
        $this->elasticHelper->deleteIndex(SearchIndex::PARISH);
        $this->elasticHelper->createIndex(SearchIndex::PARISH);
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
        self::assertEquals([0 => $dioceseIds[4]], $ids);

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

        $ids = $this->elasticService->searchParishIds('Joseph', 3, 0);
        self::assertEquals([0 => $parishes[2]['parishName']], $ids);

        $ids = $this->elasticService->searchParishIds('carmeel', 3, 0);
        self::assertEquals([0 => $parishes[1]['parishName']], $ids);

        // search by diocese name
        $ids = $this->elasticService->searchParishIds('Aire et Dax', 3, 0);
        self::assertEquals([0 => $parishes[1]['parishName'], 1 => $parishes[5]['parishName']], $ids);

        // search by diocese name
        $ids = $this->elasticService->searchParishIds('Archidiocèse', 3, 0);
        self::assertEquals([0 => $parishes[3]['parishName']], $ids);
    }

    public function testParishStopwords(): void
    {
        $parishes = [
            ['dioceseName' => "Diocèse d'Amiens", 'parishName' => 'Paroisse Saint-Domice'],
            ['dioceseName' => "Diocèse d'Aire et Dax", 'parishName' => 'Paroisse Notre-Dame-du-Mont-Carmel'],
            ['dioceseName' => "Diocèse d'Autun, Chalon et Mâcon", 'parishName' => 'Paroisse Saint-Joseph-Ouvrier'],
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

        // check if d' is a stopword
        $ids = $this->elasticService->searchParishIds("d'", 3, 0);
        self::assertCount(0, $ids);

        // check if saint is a stopword
        $ids = $this->elasticService->searchParishIds('saint', 3, 0);
        self::assertCount(0, $ids);
        $ids = $this->elasticService->searchParishIds('sain', 3, 0);
        self::assertCount(0, $ids);
        $ids = $this->elasticService->searchParishIds('sainte', 3, 0);
        self::assertCount(0, $ids);
        $ids = $this->elasticService->searchParishIds('Sainte', 3, 0);
        self::assertCount(0, $ids);

        // check if paroisse is a stopword
        $ids = $this->elasticService->searchParishIds('paroisse', 3, 0);
        self::assertCount(0, $ids);
        $ids = $this->elasticService->searchParishIds('Paroisse', 3, 0);
        self::assertCount(0, $ids);
        $ids = $this->elasticService->searchParishIds('paroiss', 3, 0);
        self::assertCount(0, $ids);

        // // check if diocèse is a stopword
        $ids = $this->elasticService->searchParishIds('diocese', 3, 0);
        self::assertCount(0, $ids);
        $ids = $this->elasticService->searchParishIds('Diocese', 3, 0);
        self::assertCount(0, $ids);
        $ids = $this->elasticService->searchParishIds('diocèse', 3, 0);
        self::assertCount(0, $ids);
        $ids = $this->elasticService->searchParishIds('dioce', 3, 0);
        self::assertCount(0, $ids);
    }
}
