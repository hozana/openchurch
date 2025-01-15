<?php

declare(strict_types=1);

namespace App\Community\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Community\Domain\Model\Community;
use App\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Community\Infrastructure\ApiPlatform\Input\CommunityFieldsInput;
use App\Field\Application\FieldService;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Exception\FieldWikidataIdMissingException;
use App\Field\Domain\Model\Field;
use App\Shared\Domain\Manager\TransactionManagerInterface;
use Exception;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Uid\UuidV7;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Webmozart\Assert\Assert;

use function Zenstruck\Foundry\Persistence\delete;

/**
 * @implements ProcessorInterface<CommunityFieldsInput, CommunityFieldsInput>
 */
final class UpsertCommunityProcessor implements ProcessorInterface
{
    public function __construct(
        private CommunityRepositoryInterface $communityRepo,
        private TransactionManagerInterface $transactionManager,
        private HttpClientInterface $httpClient,
        private UpdateCommunityProcessor $updateCommunityProcessor,
        private FieldService $fieldService,
        private DenormalizerInterface $denormalizer,
    ) {
    }

    /**
     * @param CommunityFieldsInput $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->transactionManager->transactional(function () use ($data) {
            Assert::isInstanceOf($data, CommunityFieldsInput::class);

            $wikidataIdFields = [];
            $result = [];

            $wikidataIds = array_map(function (array $fields) use(&$wikidataIdFields) {
                $wikidataField = $this->getFieldByName($fields, FieldCommunity::WIKIDATA_ID->value);
                if (!$wikidataField) throw new FieldWikidataIdMissingException();

                $wikidataId = $wikidataField['value'];
                $wikidataIdFields[$wikidataId] = $fields;
                return $wikidataId;
            }, $data->wikidataEntities);   
            
            // Update...
            $communities = $this->communityRepo->addSelectField()->withWikidataIds($wikidataIds)->asCollection();
            foreach ($communities as $community) {
                $wikidataId = $community->getMostTrustableFieldByName(FieldCommunity::WIKIDATA_ID)->getValue();
                $openChurchWikidataUpdatedAt = $community->getMostTrustableFieldByName(FieldCommunity::WIKIDATA_UPDATED_AT);
                $wikidataUpdatedAt = $this->getFieldByName($wikidataIdFields[$wikidataId], FieldCommunity::WIKIDATA_UPDATED_AT->value);
                
                if (!$openChurchWikidataUpdatedAt || $wikidataUpdatedAt['value'] !== $openChurchWikidataUpdatedAt->getValue()) {
                    // WikidataUpdatedAt is not the same. We have to update the data
                    try {
                        $this->fieldService->upsertFields($community, $this->arrayToFields($wikidataIdFields[$wikidataId]));
                        $result[$wikidataId] = 'Updated';
                    }
                    catch (Exception $e) {
                        $result[$wikidataId] = $this->handleError($e);
                    }
                }
                unset($wikidataIdFields[$wikidataId]);
            }

            // Insert...
            foreach ($wikidataIdFields as $wikidataId => $fields) {
                try {
                    $community = new Community();
                    $this->communityRepo->add($community);
                    $this->fieldService->upsertFields($community, $this->arrayToFields($fields));
                    $result[$wikidataId] = 'Inserted';
                }
                catch (Exception $e) {
                    $result[$wikidataId] = $this->handleError($e);
                }
            }

            return $result;
        });
    }

    private function arrayToFields(array $fields) : array {
        return array_map(
            fn (array $field) => $this->denormalizer->denormalize($field, Field::class),
            $fields
        );
    }

    private function getFieldByName(array $fields, string $fieldName) : mixed {
        $result = array_values(array_filter($fields, 
            fn (array $field) => $field['name'] === $fieldName
        ));

        if (count($result) > 0) {
            return $result[0];
        }

        return null;
    }

    private function handleError(Exception $e) : string {
        $this->communityRepo->clear();
        if ($e instanceof ValidationException) {
            return $e->getMessage();
        }
        if ($e instanceof ProblemExceptionInterface) {
            return $e->getDetail();
        }
        
        return $e->getMessage();
    }
}
