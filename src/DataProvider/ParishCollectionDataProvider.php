<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Parish;
use Elastica\Query;
use Elastica\Query\MatchQuery;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class ParishCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
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
        return Parish::class === $resourceClass;
    }

    public function getCollection(string $resourceClass, string $operationName = null)
    {
        $boolQuery = new Query\BoolQuery();
        $query = new Query();
        $request = $this->requestStack->getCurrentRequest();

        if ($id = $request->get('id')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('id', $id);
            $matchQuery->setFieldFuzziness('id', 0);
            $boolQuery->addMust($matchQuery);
        }
        if ($name = $request->get('name')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('name', $name);
            $matchQuery->setFieldFuzziness('name', 2);
            $boolQuery->addMust($matchQuery);
        }
        if ($name = $request->get('messesinfoId')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('messesinfoId', $name);
            $matchQuery->setFieldFuzziness('messesinfoId', 2);
            $boolQuery->addMust($matchQuery);
        }
        if ($name = $request->get('website')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('website', $name);
            $matchQuery->setFieldFuzziness('website', 2);
            $boolQuery->addMust($matchQuery);
        }
        if ($name = $request->get('zipCode')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('zipCode', $name);
            $matchQuery->setFieldFuzziness('zipCode', 2);
            $boolQuery->addMust($matchQuery);
        }
        if ($countryId = (int) $request->get('countryId')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('country.id', (string) $countryId);
            $matchQuery->setFieldFuzziness('country.id', 0);
            $boolQuery->addMust($matchQuery);
        }
        if ($countryName = $request->get('countryName')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('country.name', $countryName);
            $matchQuery->setFieldFuzziness('country.name', 2);
            $boolQuery->addMust($matchQuery);
        }
        if ($countryId = (int) $request->get('dioceseId')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('diocese.id', (string) $countryId);
            $matchQuery->setFieldFuzziness('diocese.id', 0);
            $boolQuery->addMust($matchQuery);
        }
        if ($countryName = $request->get('dioceseName')) {
            $matchQuery = new MatchQuery();
            $matchQuery->setFieldQuery('diocese.name', $countryName);
            $matchQuery->setFieldFuzziness('diocese.name', 2);
            $boolQuery->addMust($matchQuery);
        }

        $query->setQuery($boolQuery);
        $paginator = $this->finder->findPaginated($query);

        return $paginator->getCurrentPageResults();
    }
}
