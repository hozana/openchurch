<?php

declare(strict_types=1);

namespace App\FieldHolder\Place\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Field\Application\FieldService;
use App\Field\Domain\Enum\FieldPlace;
use App\Field\Domain\Exception\FieldWikidataIdMissingException;
use App\FieldHolder\Application\FieldHolderUpsertService;
use App\FieldHolder\Place\Domain\Model\Place;
use App\FieldHolder\Place\Domain\Repository\PlaceRepositoryInterface;
use App\FieldHolder\Place\Infrastructure\ApiPlatform\Input\PlaceWikidataInput;
use App\Shared\Domain\Manager\TransactionManagerInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<PlaceWikidataInput, array<int, string>>
 */
final class UpsertPlaceProcessor implements ProcessorInterface
{
    public function __construct(
        private PlaceRepositoryInterface $placeRepo,
        private TransactionManagerInterface $transactionManager,
        private FieldService $fieldService,
        private FieldHolderUpsertService $fieldHolderUpsertService,
    ) {
    }

    /**
     * @param PlaceWikidataInput $data
     *
     * @return array<int, string>
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->transactionManager->transactional(function () use ($data) {
            Assert::isInstanceOf($data, PlaceWikidataInput::class);

            $wikidataIdFields = [];
            $result = [];

            $wikidataIds = array_map(function (array $fields) use (&$wikidataIdFields) {
                $wikidataField = $this->fieldHolderUpsertService->getFieldByName($fields, FieldPlace::WIKIDATA_ID->value);
                if (!$wikidataField) {
                    throw new FieldWikidataIdMissingException();
                }
                $wikidataId = $wikidataField->value;
                $wikidataIdFields[$wikidataId] = $fields;

                return $wikidataId;
            }, $data->wikidataEntities);

            // Update...
            $places = $this->placeRepo->addSelectField()->withWikidataIds($wikidataIds)->asCollection();
            foreach ($places as $place) {
                $wikidataId = $place->getMostTrustableFieldByName(FieldPlace::WIKIDATA_ID)->getValue();
                try {
                    $this->fieldService->upsertFields($place, $wikidataIdFields[$wikidataId]);
                    $result[$wikidataId] = 'Updated';
                } catch (ProblemExceptionInterface|ValidationException $e) {
                    $result[$wikidataId] = $this->fieldHolderUpsertService->handleError($place, $e);
                }
                unset($wikidataIdFields[$wikidataId]);
            }

            // Insert...
            foreach ($wikidataIdFields as $wikidataId => $fields) {
                $place = null;
                try {
                    $place = new Place();
                    $this->placeRepo->add($place);

                    $this->fieldService->upsertFields($place, $fields);
                    $result[$wikidataId] = 'Inserted';
                } catch (ProblemExceptionInterface|ValidationException $e) {
                    $result[$wikidataId] = $this->fieldHolderUpsertService->handleError($place, $e);
                }
            }

            return $result;
        });
    }
}
