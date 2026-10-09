<?php

declare(strict_types=1);

namespace App\FieldHolder\Place\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Field\Application\FieldService;
use App\FieldHolder\Place\Domain\Repository\PlaceRepositoryInterface;
use App\FieldHolder\Place\Infrastructure\ApiPlatform\Resource\PlaceResource;
use App\Shared\Domain\Manager\TransactionManagerInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<PlaceResource, PlaceResource>
 */
final readonly class UpdatePlaceProcessor implements ProcessorInterface
{
    public function __construct(
        private PlaceRepositoryInterface $placeRepo,
        private TransactionManagerInterface $transactionManager,
        private FieldService $fieldService,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PlaceResource
    {
        return $this->transactionManager->transactional(function () use ($data) {
            Assert::isInstanceOf($data, PlaceResource::class);

            // place cannot be null because we passed through PlaceItemProvider
            Assert::notNull($data->id);
            $place = $this->placeRepo->ofId($data->id);
            Assert::notNull($place);

            $this->fieldService->upsertFields($place, $data->fields);

            return PlaceResource::fromModel($place);
        });
    }
}
