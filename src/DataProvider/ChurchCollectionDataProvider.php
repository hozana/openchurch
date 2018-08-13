<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Church;
use Doctrine\ORM\EntityManagerInterface;
use Elastica\Query;
use Elastica\QueryBuilder;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class ChurchCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private $entityManager;
    private $finder;
    private $requestStack;

    public function __construct(EntityManagerInterface $entityManager, PaginatedFinderInterface $finder, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->finder = $finder;
        $this->requestStack = $requestStack;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Church::class === $resourceClass;
    }

    public function getCollection(string $resourceClass, string $operationName = null)
    {
        $qb = new QueryBuilder();
        $boolQuery = new Query\BoolQuery();
        $query = new Query();

        if ($name = $this->requestStack->getCurrentRequest()->get('name')) {
            $nameQuery = new Query\Match();
            $nameQuery->setFieldQuery('name', $name);
            $nameQuery->setFieldFuzziness('name', 2);
            $boolQuery->addMust($nameQuery);
        }
        if ($communeId = $this->requestStack->getCurrentRequest()->get('communeId')) {
            $nestedQuery = new Query\BoolQuery();
            $communeIdQuery = new Query\Nested();
            $communeIdQuery->setPath('commune')->setQuery(
                $nestedQuery->addMust(
                    new Query\Match('commune.id', $communeId)
                )
            );
            $boolQuery->addMust($communeIdQuery);
        }
        if ($communeName = $this->requestStack->getCurrentRequest()->get('communeName')) {
            $nestedQuery = new Query\BoolQuery();
            $communeNameQuery = new Query\Nested();
            $communeNameQuery->setPath('commune')->setQuery(
                $nestedQuery->addMust(
                    new Query\Match('commune.name', $communeName)
                )
            );
            $boolQuery->addMust($communeNameQuery);
        }
        if ($wikidataId = $this->requestStack->getCurrentRequest()->get('wikidataId')) {
            $boolQuery->addMust(new Query\Match('wikidataId', $wikidataId));
        }
        if (($longitude = $this->requestStack->getCurrentRequest()->get('longitude')) && ($latitude = $this->requestStack->getCurrentRequest()->get('latitude'))) {
            $geoPoint = array('lat' => $latitude, 'lon' => $longitude);
            $boolQuery->addFilter(new Query\GeoDistance('pin', $geoPoint, '3km'));
            $query->addSort(['_geo_distance' => ['pin' => $geoPoint, 'order' => 'asc']]);
        }

        $query->setQuery($boolQuery);
        //die(var_dump(json_encode($query->toArray())));

        $paginator = $this->finder->findPaginated($query);

        return $paginator->getCurrentPageResults();
    }
}
