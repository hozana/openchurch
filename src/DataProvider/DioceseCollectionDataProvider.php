<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Diocese;
use Doctrine\ORM\EntityManagerInterface;
use Elastica\Query;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class DioceseCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
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
        return Diocese::class === $resourceClass;
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
            $matchQuery->setFieldQuery('name', $name);
            $matchQuery->setFieldFuzziness('name', 2);
            $boolQuery->addMust($matchQuery);
        }
        if ($countryId = (int) $request->get('countryId')) {
            $matchQuery = new Query\Match();
            $matchQuery->setFieldQuery('country.id', (string) $countryId);
            $matchQuery->setFieldFuzziness('country.id', 0);
            $boolQuery->addMust($matchQuery);
        }
        if ($countryName = $request->get('countryName')) {
            $matchQuery = new Query\Match();
            $matchQuery->setFieldQuery('country.name', $countryName);
            $matchQuery->setFieldFuzziness('country.name', 2);
            $boolQuery->addMust($matchQuery);
        }

        $query->setQuery($boolQuery);
        $paginator = $this->finder->findPaginated($query);

        return $paginator->getCurrentPageResults();
    }
}
