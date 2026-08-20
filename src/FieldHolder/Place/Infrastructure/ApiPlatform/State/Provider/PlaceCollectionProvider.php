<?php

declare(strict_types=1);

namespace App\FieldHolder\Place\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use App\Field\Domain\Enum\FieldCommunity;
use App\FieldHolder\Place\Domain\Repository\PlaceRepositoryInterface;
use App\FieldHolder\Place\Infrastructure\ApiPlatform\Resource\PlaceResource;
use App\Shared\Domain\Cast;
use App\Shared\Infrastructure\ApiPlatform\State\Paginator;
use ArrayIterator;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<PlaceResource>
 */
final readonly class PlaceCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Pagination $pagination,
        private PlaceRepositoryInterface $placeRepo,
    ) {
    }

    /**
     * @return Paginator<PlaceResource>|list<PlaceResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Paginator|array
    {
        $filters = is_array($context['filters'] ?? null) ? $context['filters'] : [];

        $rawParentCommunityId = $filters[FieldCommunity::PARENT_COMMUNITY_ID->value] ?? null;
        $parentCommunityId = null;

        if (null !== $rawParentCommunityId) {
            if (!is_string($rawParentCommunityId) || !Uuid::isValid($rawParentCommunityId)) {
                throw new InvalidArgumentException(sprintf('provided parentCommunityId %s is not a valid uuid', Cast::toString($rawParentCommunityId)));
            }

            $parentCommunityId = Uuid::fromString($rawParentCommunityId);
        }

        $page = $itemsPerPage = null;

        if ($this->pagination->isEnabled($operation, $context)) {
            $page = $this->pagination->getPage($context);
            $itemsPerPage = $this->pagination->getLimit($operation, $context);
        }

        $models = $this->placeRepo
            ->withParentCommunityId($parentCommunityId)
        ;

        $models = null !== $page && null !== $itemsPerPage
            ? $models->withPagination($page, $itemsPerPage)
            : $models->withoutPagination();

        $resources = [];
        foreach ($models as $model) {
            $resources[] = PlaceResource::fromModel($model);
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
