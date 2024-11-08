<?php

declare(strict_types=1);

namespace App\Community\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use App\Community\Infrastructure\ApiPlatform\Resource\CommunityResource;
use App\Community\Infrastructure\Doctrine\DoctrineCommunityRepository;
use App\Shared\Infrastructure\ApiPlatform\State\Paginator;

final class CommunityCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Pagination $pagination,
        private DoctrineCommunityRepository $communityRepo,
    ) {
    }

    /**
     * @return Paginator<BookResource>|list<BookResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Paginator|array
    {
        /** @var string|null $type */
        $type = $context['filters']['type'] ?? null;
        $wikidataId = $context['filters']['wikidataId'] ?? null;
        $page = $itemsPerPage = null;

        if ($this->pagination->isEnabled($operation, $context)) {
            $page = $this->pagination->getPage($context);
            $itemsPerPage = $this->pagination->getLimit($operation, $context);
        }

        $models = $this->communityRepo
            ->withType($type)
            ->withWikidataId($wikidataId)
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