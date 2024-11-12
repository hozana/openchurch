<?php

declare(strict_types=1);

namespace App\Place\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Field\Application\FieldService;
use App\Place\Domain\Model\Place;
use App\Place\Domain\Repository\PlaceRepositoryInterface;
use App\Place\Infrastructure\ApiPlatform\Payload\CreatePlacePayload;
use App\Place\Infrastructure\ApiPlatform\Resource\PlaceResource;
use App\Shared\Domain\Manager\TransactionManagerInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<CreatePlacePayload, PlaceResource>
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
     * @param CreatePlacePayload $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PlaceResource
    {
        return $this->transactionManager->transactional(function () use ($data) {
            Assert::isInstanceOf($data, CreatePlacePayload::class);

            $community = new Place();
            $this->placeRepo->add($community);

            $fields = $this->fieldService->upsertFields($community, $data->fields);
            return new PlaceResource(
                $community->id,
                $fields
            );
        });
    }
}