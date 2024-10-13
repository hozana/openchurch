<?php

declare(strict_types=1);

namespace App\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use App\BookStore\Infrastructure\Doctrine\DoctrineCommunityRepository;
use App\Shared\Infrastructure\ApiPlatform\State\Paginator;

final readonly class CommunityCollectionProvider implements ProviderInterface
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
        $typeValue = $context['filters']['type'] ?? null;
        $offset = $limit = null;

        if ($this->pagination->isEnabled($operation, $context)) {
            $offset = $this->pagination->getPage($context);
            $limit = $this->pagination->getLimit($operation, $context);
        }

        $toto = $this->communityRepo
            ->withType($typeValue)
            ->withPagination(1, 10);

            dd($toto);

    }
}