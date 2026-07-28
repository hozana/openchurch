<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use App\Field\Domain\Enum\FieldCommunity;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Exception\CommunityTypeNotProvidedException;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Community\Domain\Service\SearchServiceInterface;
use App\FieldHolder\Community\Infrastructure\ApiPlatform\Resource\CommunityResource;
use App\Shared\Infrastructure\ApiPlatform\State\Paginator;
use ArrayIterator;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<CommunityResource>
 */
final readonly class CommunityCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Pagination $pagination,
        private CommunityRepositoryInterface $communityRepo,
        private SearchServiceInterface $searchService,
    ) {
    }

    /**
     * @return Paginator<CommunityResource>|list<CommunityResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Paginator|array
    {
        $filters = is_array($context['filters'] ?? null) ? $context['filters'] : [];

        $type = is_string($rawType = $filters[FieldCommunity::TYPE->value] ?? null) ? $rawType : null;
        $wikidataId = $filters[FieldCommunity::WIKIDATA_ID->value] ?? null;
        $parentWikidataId = $filters[FieldCommunity::PARENT_WIKIDATA_ID->value] ?? null;
        $contactZipcodes = $filters['contactZipcodes'] ?? null;

        $name = is_string($rawName = $filters[FieldCommunity::NAME->value] ?? null) ? $rawName : null;
        $page = $itemsPerPage = null;

        if ($this->pagination->isEnabled($operation, $context)) {
            $page = $this->pagination->getPage($context);
            $itemsPerPage = $this->pagination->getLimit($operation, $context);
        }

        $parentCommunity = null;
        if ($parentWikidataId) {
            $parentCommunity = $this->communityRepo->withWikidataId(is_numeric($parentWikidataId) ? (int) $parentWikidataId : null)->asCollection()->first() ?: null;
        }

        // name is provided. We search through elastic
        if ($name !== null) {
            if (!$type) {
                throw new CommunityTypeNotProvidedException();
            }

            $entityIds = match ($type) {
                CommunityType::PARISH->value => $this->searchService->searchParishIds($name, $parentCommunity?->id?->toString(), $itemsPerPage ?? 0, ($page ?? 1) - 1),
                CommunityType::DIOCESE->value => $this->searchService->searchDioceseIds($name, $itemsPerPage ?? 0, ($page ?? 1) - 1),
                default => throw new InvalidArgumentException(sprintf('Invalid type %s', $type)),
            };

            if ([] === $entityIds) {
                return [];
            }
        }

        $models = $this->communityRepo
            ->ofIds(array_map(Uuid::fromString(...), $entityIds ?? []))
            ->withType($type)
            ->withWikidataId(is_numeric($wikidataId) ? (int) $wikidataId : null)
            ->withParentCommunityId($parentCommunity?->id)
            ->withContactZipcodes(is_array($contactZipcodes) ? array_values(array_filter($contactZipcodes, is_string(...))) : null)
            ->withActive();

        $models = null !== $page && null !== $itemsPerPage
            ? $models->withPagination($page, $itemsPerPage)
            : $models->withoutPagination();

        if ($name === null) {
            $models = $models->sortByName();
        }

        $resources = [];
        foreach ($models as $model) {
            $resources[] = CommunityResource::fromModel($model);
        }

        if (null !== $paginator = $models->paginator()) {
            $resources = new Paginator(
                new ArrayIterator($resources),
                (float) $paginator->getCurrentPage(),
                (float) $paginator->getItemsPerPage(),
                (float) $paginator->getLastPage(),
                (float) $paginator->getTotalItems(),
            );
        }

        return $resources;
    }
}
