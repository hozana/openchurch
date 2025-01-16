<?php

declare(strict_types=1);

namespace App\FieldHolder\Place\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\FieldHolder\Place\Domain\Exception\PlaceNotFoundException;
use App\FieldHolder\Place\Domain\Repository\PlaceRepositoryInterface;
use App\FieldHolder\Place\Infrastructure\ApiPlatform\Resource\PlaceResource;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProviderInterface<PlaceResource>
 */
final class PlaceItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly PlaceRepositoryInterface $placeRepo,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): PlaceResource
    {
        /** @var Uuid $id */
        $id = $uriVariables['id'];

        $place = $this->placeRepo->ofId($id);

        if (null === $place) {
            throw new PlaceNotFoundException($id);
        }

        return PlaceResource::fromModel($place);
    }
}
