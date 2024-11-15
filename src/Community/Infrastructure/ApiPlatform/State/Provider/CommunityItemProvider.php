<?php

declare(strict_types=1);

namespace App\Community\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Community\Domain\Exception\CommunityNotFoundException;
use App\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Community\Infrastructure\ApiPlatform\Resource\CommunityResource;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<CommunityResource>
 */
final class CommunityItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly CommunityRepositoryInterface $communityRepo,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CommunityResource
    {
        /** @var Uuid $id */
        $id = $uriVariables['id'];

        $community = $this->communityRepo->ofId($id);

        if (null === $community) {
            throw new CommunityNotFoundException($id);
        }

        return CommunityResource::fromModel($community);
    }
}