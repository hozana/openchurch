<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Field\Application\FieldService;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Exception\FieldWikidataIdMissingException;
use App\FieldHolder\FieldHolderUpsertService;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Community\Infrastructure\ApiPlatform\Input\CommunityWikidataInput;
use App\Shared\Domain\Manager\TransactionManagerInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<CommunityWikidataInput, array<int, string>>
 */
final class UpsertCommunityProcessor implements ProcessorInterface
{
    public function __construct(
        private CommunityRepositoryInterface $communityRepo,
        private TransactionManagerInterface $transactionManager,
        private FieldService $fieldService,
        private FieldHolderUpsertService $fieldHolderUpsertService,
    ) {
    }

    /**
     * @param CommunityWikidataInput $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->transactionManager->transactional(function () use ($data) {
            Assert::isInstanceOf($data, CommunityWikidataInput::class);

            $wikidataIdFields = [];
            $result = [];

            $wikidataIds = array_map(function (array $fields) use (&$wikidataIdFields) {
                $wikidataField = $this->fieldHolderUpsertService->getFieldByName($fields, FieldCommunity::WIKIDATA_ID->value);
                if (!$wikidataField) {
                    throw new FieldWikidataIdMissingException();
                }

                $wikidataId = $wikidataField->value;
                $wikidataIdFields[$wikidataId] = $fields;

                return $wikidataId;
            }, $data->wikidataEntities);

            // Update...
            $communities = $this->communityRepo->addSelectField()->withWikidataIds($wikidataIds)->asCollection();
            foreach ($communities as $community) {
                $wikidataId = $community->getMostTrustableFieldByName(FieldCommunity::WIKIDATA_ID)->getValue();
                try {
                    $this->fieldService->upsertFields($community, $wikidataIdFields[$wikidataId]);
                    $result[$wikidataId] = 'Updated';
                } catch (ProblemExceptionInterface|ValidationException $e) {
                    $result[$wikidataId] = $this->fieldHolderUpsertService->handleError($community, $e);
                }
                unset($wikidataIdFields[$wikidataId]);
            }

            // Insert...
            foreach ($wikidataIdFields as $wikidataId => $fields) {
                $community = null;
                try {
                    $community = new Community();
                    $this->communityRepo->add($community);

                    $this->fieldService->upsertFields($community, $fields);
                    $result[$wikidataId] = 'Inserted';
                } catch (ProblemExceptionInterface|ValidationException $e) {
                    $result[$wikidataId] = $this->fieldHolderUpsertService->handleError($community, $e);
                }
            }

            return $result;
        });
    }
}
