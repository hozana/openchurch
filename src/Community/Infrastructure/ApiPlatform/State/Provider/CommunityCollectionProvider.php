<?php

declare(strict_types=1);

namespace App\Community\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use App\Community\Domain\Enum\CommunityType;
use App\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Community\Infrastructure\ApiPlatform\Resource\CommunityResource;
use App\Core\Domain\Search\Service\SearchServiceInterface;
use App\Field\Domain\Enum\FieldCommunity;
use App\Shared\Infrastructure\ApiPlatform\State\Paginator;
use InvalidArgumentException;

use function PHPUnit\Framework\assertNotNull;

final class CommunityCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Pagination $pagination,
        private CommunityRepositoryInterface $communityRepo,
        private SearchServiceInterface $searchService,
    ) {
    }

    /**
     * @return Paginator<BookResource>|list<BookResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Paginator|array
    {
        /** @var string|null $type */
        $type = $context['filters'][FieldCommunity::TYPE->value] ?? null;
        $wikidataId = $context['filters'][FieldCommunity::WIKIDATA_ID->value] ?? null;
        $name = $context['filters'][FieldCommunity::NAME->value] ?? null;
        $page = $itemsPerPage = null;

        if ($this->pagination->isEnabled($operation, $context)) {
            $page = $this->pagination->getPage($context);
            $itemsPerPage = $this->pagination->getLimit($operation, $context);
        }

        // name is provided. We search through elastic
        if ($name) {
            assertNotNull($type);
            $entityIds = match ($type) {
                CommunityType::PARISH->value => $this->searchService->searchParishIds($name, $itemsPerPage, $page - 1),
                CommunityType::DIOCESE->value => $this->searchService->searchDioceseIds($name, $itemsPerPage, $page - 1),
                default => throw new InvalidArgumentException(sprintf('Invalid type %s', $type)),
            };

            if (count($entityIds) === 0) {
                return [];
            }
        }

        $models = $this->communityRepo
            ->ofIds($entityIds ?? [])
            ->withType($type)
            ->withWikidataId((int) $wikidataId)
            ->withPagination($page, $itemsPerPage);

        $resources = [];
        foreach ($models as $model) {
            $resources[] = CommunityResource::fromModel($model);
        }

        if (null !== $paginator = $models->paginator()) {
            $resources = new Paginator(
                new \ArrayIterator($resources),
                (float) $paginator->getCurrentPage(),
                (float) $paginator->getItemsPerPage(),
                (float) $paginator->getLastPage(),
                (float) $paginator->getTotalItems(),
            );
        }

        return $resources;
    }
}