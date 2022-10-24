<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Parish;
use Elastica\Query;
use Elastica\Query\MatchQuery;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class ParishCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
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

        return Parish::class === $resourceClass;
    }

    /**
     * @return iterable<Parish>
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
        if ($messesinfoId = $request->get('messesinfoId')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('messesinfoId', $messesinfoId);
            $matchQuery->setFieldFuzziness('messesinfoId', 2);
            $boolQuery->addMust($matchQuery);
        }
        if ($website = $request->get('website')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('website', $website);
            $matchQuery->setFieldFuzziness('website', 2);
            $boolQuery->addMust($matchQuery);
        }
        if ($zipCode = $request->get('zipCode')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('zipCode', $zipCode);
            $matchQuery->setFieldFuzziness('zipCode', 2);
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
        if ($dioceseId = (int) $request->get('dioceseId')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('diocese.id', (string) $dioceseId);
            $boolQuery->addMust($matchQuery);
        }
        if ($dioceseName = $request->get('dioceseName')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('diocese.name', $dioceseName);
            $matchQuery->setFieldFuzziness('diocese.name', 2);
            $boolQuery->addMust($matchQuery);
        }

        $query->setQuery($boolQuery);
        /** @var Pagerfanta<Parish> */
        $paginator = $this->finder->findPaginated($query);
        try {
            $paginator->setCurrentPage($this->context['filters']['page'] ?? 1);
        } catch (OutOfRangeCurrentPageException $e) {
            return [];
        }

        return $paginator->getCurrentPageResults();
    }
}
