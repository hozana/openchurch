<?php

declare(strict_types=1);

namespace App\Place\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Field\Application\FieldService;
use App\Place\Domain\Model\Place;
use App\Place\Domain\Repository\PlaceRepositoryInterface;
use App\Place\Infrastructure\ApiPlatform\Resource\PlaceResource;
use App\Shared\Domain\Manager\TransactionManagerInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<PlaceResource, PlaceResource>
 */
final class CreatePlaceProcessor implements ProcessorInterface
{
    public function __construct(
        private PlaceRepositoryInterface $placeRepo,
        private TransactionManagerInterface $transactionManager,
        private FieldService $fieldService,
    ) {
    }

    /**
     * @param PlaceResource $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PlaceResource
    {
        return $this->transactionManager->transactional(function () use ($data) {
            Assert::isInstanceOf($data, PlaceResource::class);

            $place = new Place();
            $this->placeRepo->add($place);

            $this->fieldService->upsertFields($place, $data->fields);

            return PlaceResource::fromModel(
                $place,
            );
        });
    }
}
