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
        $query = new Query\BoolQuery();

        if ($name = $this->requestStack->getCurrentRequest()->get('name')) {
            $nameQuery = new Query\QueryString();
            $nameQuery->setQuery($name)->setDefaultField('name');
            $query->addMust($nameQuery);
        }
        if ($communeId = $this->requestStack->getCurrentRequest()->get('communeId')) {
            $nestedQuery = new Query\BoolQuery();
            $idQuery = new Query\QueryString();
            $idQuery->setQuery($communeId)->setDefaultField('id');

            $communeIdQuery = new Query\Nested();
            $communeIdQuery->setPath('commune')->setQuery($nestedQuery->addMust($idQuery));
            $query->addMust($communeIdQuery);
        }
        if ($communeName = $this->requestStack->getCurrentRequest()->get('communeName')) {
            $communeNameQuery = $qb->query()->nested()
                ->setPath('commune')
                ->setQuery(
                    $qb->query()->bool()
                        ->addMust($qb->query()->match(
                            'commune.name',
                            $communeName
                        ))
                );
            $query->addMust($communeNameQuery);
        }
        if ($wikidataId = $this->requestStack->getCurrentRequest()->get('wikidataId')) {
            $wikidataIdQuery = new Query\QueryString();
            $wikidataIdQuery->setQuery($wikidataId)->setDefaultField('wikidataId');
            $query->addMust($wikidataIdQuery);
        }

        $paginator = $this->finder->findPaginated($query);

        return $paginator->getCurrentPageResults();
    }
}
