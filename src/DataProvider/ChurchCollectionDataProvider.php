<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Church;
use Doctrine\ORM\EntityManagerInterface;
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

        $name = $this->requestStack->getCurrentRequest()->get('name');
        $this->finder->findPaginated($name);
        $churches = $this->entityManager->getRepository(Church::class)->findBy(['name' => $name]);

        return $churches;
    }
}
