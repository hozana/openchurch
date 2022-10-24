<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Diocese;
use Elastica\Query;
use Elastica\Query\MatchQuery;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class DioceseCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
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
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        $this->context = $context;

        return Diocese::class === $resourceClass;
    }

    /**
     * @return iterable<Diocese>
     */
    public function getCollection(string $resourceClass, string $operationName = null)
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
            $matchQuery->setFieldQuery('name', $name);
            $matchQuery->setFieldFuzziness('name', 2);
            $boolQuery->addMust($matchQuery);
        }
        if ($countryId = (int) $request->get('countryId')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('country.id', (string) $countryId);
            $boolQuery->addMust($matchQuery);
        }
        if ($countryName = $request->get('countryName')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('country.name', $countryName);
            $matchQuery->setFieldFuzziness('country.name', 2);
            $boolQuery->addMust($matchQuery);
        }

        $query->setQuery($boolQuery);
        /** @var Pagerfanta<Diocese> */
        $paginator = $this->finder->findPaginated($query);
        try {
            $paginator->setCurrentPage($this->context['filters']['page'] ?? 1);
        } catch (OutOfRangeCurrentPageException $e) {
            return [];
        }

        return $paginator->getCurrentPageResults();
    }
}
