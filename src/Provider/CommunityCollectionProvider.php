<?php

declare(strict_types=1);

namespace App\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use App\Shared\Infrastructure\ApiPlatform\State\Paginator;

final readonly class CommunityCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Pagination $pagination,
    ) {
    }

    /**
     * @return Paginator<BookResource>|list<BookResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Paginator|array
    {
        /** @var string|null $author */
        $type = $context['filters']['type'] ?? null;

        
    }
}