<?php

namespace App\Tests\Integration\Service;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Core\Infrastructure\Helper\OfficialElasticSearchHelper;
use App\Core\Infrastructure\Service\OfficialElasticSearchService;
use App\Shared\Domain\Enum\SearchIndex;
use App\Tests\Helper\AcceptanceTestHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class OfficialElasticSearchServiceTest extends ApiTestCase
{
    protected Client $client;
    public OfficialElasticSearchHelper $elasticHelper;
    public OfficialElasticSearchService $elasticService;

    protected function setUp(): void {
        parent::setUp();
        $this->client = static::createClient();

        /** @var OfficialElasticSearchHelper $elasticHelper  */
        $this->elasticHelper = new OfficialElasticSearchHelper($_ENV['ELASTICSEARCH_IRI']);
        $this->elasticService = new OfficialElasticSearchService($this->elasticHelper);

        $this->elasticHelper->deleteIndex(SearchIndex::DIOCESE);
        $this->elasticHelper->createIndex(SearchIndex::DIOCESE);
    }

    public function testSimpleDioceseQuery(): void
    {
        $this->elasticHelper->bulkIndex(
            SearchIndex::DIOCESE, 
            [
                1,
                2,
                3,
                4,
                5
            ], 
            [
                ['dioceseName' => "Diocèse d'Avignon"],
                ['dioceseName' => "Diocèse d'Aire de Dax"],
                ['dioceseName' => "Diocèse de Beauvais, Noyon et Senlis"],
                ['dioceseName' => "Archidiocèse de Montpellier"],
                ['dioceseName' => "Diocèse de Montauban"],
            ]
        );

        $this->elasticHelper->refresh(SearchIndex::DIOCESE);
        $ids = $this->elasticService->searchDioceseIds('Montauban', 3, 0);
        self::assertEquals([0 => "5"], $ids);

        // check if d' is a stopword
        $ids = $this->elasticService->searchDioceseIds("d'Aire", 3, 0);
        self::assertEquals([0 => "2"], $ids);
    }
}