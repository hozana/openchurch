<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Church;
use Elastica\Query;
use Elastica\Query\MatchQuery;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class ChurchCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    /** @var array<mixed> */
    private array $context = [];

    private PaginatedFinderInterface $finder;
    private RequestStack $requestStack;

    public function __construct(PaginatedFinderInterface $finder, RequestStack $requestStack)
    {
        $this->finder = $finder;
        $this->requestStack = $requestStack;
    }

    /**
     * @param array<mixed> $context
     */
    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        $this->context = $context;

        return Church::class === $resourceClass;
    }

    /**
     * @return iterable<Church>
     */
    public function getCollection(string $resourceClass, ?string $operationName = null)
    {
        $boolQuery = new Query\BoolQuery();
        $query = new Query();

        /** @var Request */
        $request = $this->requestStack->getCurrentRequest();

        if ($id = (int) $request->get('id')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('id', (string) $id);
            $boolQuery->addMust($matchQuery);
        }
        if ($name = $request->get('name')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('wikidataChurch.name', $name);
            $matchQuery->setFieldFuzziness('wikidataChurch.name', 2);
            $boolQuery->addMust($matchQuery);
        }
        if ($placeId = (int) $request->get('placeId')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('wikidataChurch.place.id', (string) $placeId);
            $boolQuery->addMust($matchQuery);
        }
        if ($placeName = $request->get('placeName')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('wikidataChurch.place.name', $placeName);
            $matchQuery->setFieldFuzziness('wikidataChurch.place.name', 2);
            $boolQuery->addMust($matchQuery);
        }
        if ($wikidataChurchId = (int) $request->get('wikidataId')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('wikidataChurch.id', (string) $wikidataChurchId);
            $boolQuery->addMust($matchQuery);
        }
        if (($longitude = $request->get('longitude')) && ($latitude = $request->get('latitude'))) {
            $geoPoint = ['lat' => $latitude, 'lon' => $longitude];
            $boolQuery->addFilter(new Query\GeoDistance('wikidataChurch.pin', $geoPoint, '3km'));
            $query->addSort(['_geo_distance' => ['wikidataChurch.pin' => $geoPoint, 'order' => 'asc']]);
        }
        if ($dioceseId = (int) $request->get('dioceseId')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('wikidataChurch.diocese.id', (string) $dioceseId);
            $boolQuery->addMust($matchQuery);
        }
        if ($dioceseName = $request->get('dioceseName')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('wikidataChurch.diocese.name', $dioceseName);
            $matchQuery->setFieldFuzziness('wikidataChurch.diocese.name', 2);
            $boolQuery->addMust($matchQuery);
        }
        if ($parishId = (int) $request->get('parishId')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('wikidataChurch.parish.id', (string) $parishId);
            $boolQuery->addMust($matchQuery);
        }
        if ($parishName = $request->get('parishName')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('wikidataChurch.parish.name', $parishName);
            $matchQuery->setFieldFuzziness('wikidataChurch.parish.name', 2);
            $boolQuery->addMust($matchQuery);
        }

        $query->setQuery($boolQuery);

        /** @var Pagerfanta<Church> */
        $paginator = $this->finder->findPaginated($query);
        try {
            $paginator->setCurrentPage($this->context['filters']['page'] ?? 1);
        } catch (OutOfRangeCurrentPageException $e) {
            return [];
        }

        return $paginator->getCurrentPageResults();
    }
}
