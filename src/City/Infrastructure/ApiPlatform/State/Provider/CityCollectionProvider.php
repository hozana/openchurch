<?php

declare(strict_types=1);

namespace App\City\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use App\City\Infrastructure\ApiPlatform\Resource\CityResource;
use App\Core\Domain\Search\Service\SearchServiceInterface;
use App\Shared\Infrastructure\ApiPlatform\State\Paginator;

/**
 * @implements ProviderInterface<CityResource>
 */
final class CityCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Pagination $pagination,
        private SearchServiceInterface $searchService,
    ) {
    }

    /**
     * @return Paginator<CityResource>|list<CityResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Paginator|array
    {
        $name = $context['filters']['name'] ?? null;
        $page = $itemsPerPage = null;
        $cities = [];

        if ($this->pagination->isEnabled($operation, $context)) {
            $page = $this->pagination->getPage($context);
            $itemsPerPage = $this->pagination->getLimit($operation, $context);
        }

        if ($name !== null) {
            $cities = $this->searchService->searchCities($name, $itemsPerPage, $page - 1);
        };

        $resources = [];
        foreach ($cities as $city) {
            $resources[] = CityResource::fromModel($city);
        }

        return $resources;
    }
}
