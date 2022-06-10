<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Church;
use Elastica\Query;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class ChurchCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private PaginatedFinderInterface $finder;
    private RequestStack $requestStack;

    public function __construct(PaginatedFinderInterface $finder, RequestStack $requestStack)
    {
        $this->finder = $finder;
        $this->requestStack = $requestStack;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Church::class === $resourceClass;
    }

    public function getCollection(string $resourceClass, string $operationName = null)
    {
        $boolQuery = new Query\BoolQuery();
        $query = new Query();
        $request = $this->requestStack->getCurrentRequest();

        if ($id = $request->get('id')) {
            $matchQuery = new Query\Match();
            $matchQuery->setFieldQuery('id', $id);
            $matchQuery->setFieldFuzziness('id', 0);
            $boolQuery->addMust($matchQuery);
        }
        if ($name = $request->get('name')) {
            $matchQuery = new Query\Match();
            $matchQuery->setFieldQuery('wikidataChurch.name', $name);
            $matchQuery->setFieldFuzziness('wikidataChurch.name', 2);
            $boolQuery->addMust($matchQuery);
        }
        if ($placeId = (int) $request->get('placeId')) {
            $matchQuery = new Query\Match();
            $matchQuery->setFieldQuery('wikidataChurch.place.id', (string) $placeId);
            $matchQuery->setFieldFuzziness('wikidataChurch.place.id', 0);
            $boolQuery->addMust($matchQuery);
        }
        if ($placeName = $request->get('placeName')) {
            $matchQuery = new Query\Match();
            $matchQuery->setFieldQuery('wikidataChurch.place.name', $placeName);
            $matchQuery->setFieldFuzziness('wikidataChurch.place.name', 2);
            $boolQuery->addMust($matchQuery);
        }
        if ($wikidataChurchId = (int) $request->get('wikidataId')) {
            $matchQuery = new Query\Match();
            $matchQuery->setFieldQuery('wikidataChurch.id', (string) $wikidataChurchId);
            $matchQuery->setFieldFuzziness('wikidataChurch.id', 0);
            $boolQuery->addMust($matchQuery);
        }
        if (($longitude = $request->get('longitude')) && ($latitude = $request->get('latitude'))) {
            $geoPoint = ['lat' => $latitude, 'lon' => $longitude];
            $boolQuery->addFilter(new Query\GeoDistance('wikidataChurch.pin', $geoPoint, '3km'));
            $query->addSort(['_geo_distance' => ['wikidataChurch.pin' => $geoPoint, 'order' => 'asc']]);
        }

        $query->setQuery($boolQuery);
        $paginator = $this->finder->findPaginated($query);

        return $paginator->getCurrentPageResults();
    }
}
