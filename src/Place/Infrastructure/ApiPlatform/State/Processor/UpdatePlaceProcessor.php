<?php

declare(strict_types=1);

namespace App\Place\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Field\Application\FieldService;
use App\Place\Domain\Exception\PlaceNotFoundException;
use App\Place\Domain\Repository\PlaceRepositoryInterface;
use App\Place\Infrastructure\ApiPlatform\Resource\PlaceResource;
use App\Shared\Domain\Manager\TransactionManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<PlaceResource>
 */
final class UpdatePlaceProcessor implements ProcessorInterface
{
    public function __construct(
        private PlaceRepositoryInterface $placeRepo,
        private TransactionManagerInterface $transactionManager,
        private FieldService $fieldService,
        private Security $security,
    ) {
    }

    /**
     * @return PlaceResource
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PlaceResource
    {
        return $this->transactionManager->transactional(function () use ($data) {
            Assert::isInstanceOf($data, PlaceResource::class);

            $place = $this->placeRepo->ofId($data->id);
            if (!$place) {
                throw new PlaceNotFoundException($data->id);
            }
            
            $place->fields = $this->fieldService->upsertFields($place, $data->fields);

            return PlaceResource::fromModel($place);
        });
    }
}