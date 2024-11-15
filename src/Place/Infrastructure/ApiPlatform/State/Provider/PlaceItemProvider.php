<?php

declare(strict_types=1);

namespace App\Place\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Place\Domain\Exception\MyDomainException;
use App\Place\Domain\Exception\PlaceNotFoundException;
use App\Place\Domain\Repository\PlaceRepositoryInterface;
use App\Place\Infrastructure\ApiPlatform\Resource\PlaceResource;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
            throw new HttpException(404, 'Place.not-found');
        }

        return PlaceResource::fromModel($place);
    }
}