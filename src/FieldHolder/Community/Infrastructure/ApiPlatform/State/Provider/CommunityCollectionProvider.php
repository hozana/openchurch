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
final class CommunityCollectionProvider implements ProviderInterface
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
        /** @var string|null $type */
        $type = $context['filters'][FieldCommunity::TYPE->value] ?? null;
        $wikidataId = $context['filters'][FieldCommunity::WIKIDATA_ID->value] ?? null;
        $parentWikidataId = $context['filters'][FieldCommunity::PARENT_WIKIDATA_ID->value] ?? null;
        $contactZipcodes = $context['filters']['contactZipcodes'] ?? null;

        $name = $context['filters'][FieldCommunity::NAME->value] ?? null;
        $page = $itemsPerPage = null;

        if ($this->pagination->isEnabled($operation, $context)) {
            $page = $this->pagination->getPage($context);
            $itemsPerPage = $this->pagination->getLimit($operation, $context);
        }

        $parentCommunity = null;
        if ($parentWikidataId) {
            $parentCommunity = $this->communityRepo->withWikidataId((int) $parentWikidataId)->asCollection()->first();
        }

        // name is provided. We search through elastic
        if ($name !== null) {
            if (!$type) {
                throw new CommunityTypeNotProvidedException();
            }

            $entityIds = match ($type) {
                CommunityType::PARISH->value => $this->searchService->searchParishIds($name, $parentCommunity?->id?->toString(), $itemsPerPage, $page - 1),
                CommunityType::DIOCESE->value => $this->searchService->searchDioceseIds($name, $itemsPerPage, $page - 1),
                default => throw new InvalidArgumentException(sprintf('Invalid type %s', $type)),
            };

            if (0 === count($entityIds)) {
                return [];
            }
        }

        $models = $this->communityRepo
            ->ofIds(array_map(fn (string $entityId) => Uuid::fromString($entityId), $entityIds ?? []))
            ->withType($type)
            ->withWikidataId((int) $wikidataId)
            ->withParentCommunityId($parentCommunity->id ?? null)
            ->withContactZipcodes($contactZipcodes)
            ->withPagination($page, $itemsPerPage);

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
