<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Community\Infrastructure\ApiPlatform\Input\CommunityWikidataInput;
use App\Field\Application\FieldService;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Exception\FieldWikidataIdMissingException;
use App\FieldHolder\Application\FieldHolderUpsertService;
use App\Shared\Domain\Manager\TransactionManagerInterface;
use DateTimeImmutable;
use Exception;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<CommunityWikidataInput, CommunityWikidataInput>
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

            $wikidataIds = array_map(function (array $fields) use(&$wikidataIdFields) {
                $wikidataField = $this->fieldHolderUpsertService->getFieldByName($fields, FieldCommunity::WIKIDATA_ID->value);
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
                $wikidataUpdatedAt = $this->fieldHolderUpsertService->getFieldByName($wikidataIdFields[$wikidataId], FieldCommunity::WIKIDATA_UPDATED_AT->value);
                
                if (
                    !$openChurchWikidataUpdatedAt || 
                    intval((new DateTimeImmutable($wikidataUpdatedAt['value']))->diff($openChurchWikidataUpdatedAt->getValue())->format('%a')) >= 1
                ) {
                    // WikidataUpdatedAt diff is greater than 1 day. We have to update the data
                    try {
                        $this->fieldService->upsertFields($community, $this->fieldHolderUpsertService->arrayToFields($wikidataIdFields[$wikidataId]));
                        $result[$wikidataId] = 'Updated';
                    }
                    catch (Exception $e) {
                        $result[$wikidataId] = $this->fieldHolderUpsertService->handleError($community, $e, [$this->communityRepo, 'detach']);
                    }
                }
                else {
                    $result[$wikidataId] = 'No need to update';
                }
                unset($wikidataIdFields[$wikidataId]);
            }

            // Insert...
            foreach ($wikidataIdFields as $wikidataId => $fields) {
                try {
                    // var_dump($wikidataId);
                    $community = new Community();
                    $this->communityRepo->add($community);

                    $this->fieldService->upsertFields($community, $this->fieldHolderUpsertService->arrayToFields($fields));
                    $result[$wikidataId] = 'Inserted';
                }
                catch (Exception $e) {
                    $result[$wikidataId] = $this->fieldHolderUpsertService->handleError($community, $e, [$this->communityRepo, 'detach']);
                }
            }

            return $result;
        });
    }
}
