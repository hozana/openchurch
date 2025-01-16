<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\FieldHolder\Community\Domain\Exception\CommunityNotFoundException;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Community\Infrastructure\ApiPlatform\Resource\CommunityResource;
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
