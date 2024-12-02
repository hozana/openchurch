<?php

declare(strict_types=1);

namespace App\Community\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Community\Domain\Exception\CommunityNotFoundException;
use App\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Community\Infrastructure\ApiPlatform\Resource\CommunityResource;
use App\Field\Application\FieldService;
use App\Shared\Domain\Manager\TransactionManagerInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<CommunityResource>
 */
final class UpdateCommunityProcessor implements ProcessorInterface
{
    public function __construct(
        private CommunityRepositoryInterface $communityRepo,
        private TransactionManagerInterface $transactionManager,
        private FieldService $fieldService,
    ) {
    }

    /**
     * @param CommunityResource $data
     * @return CommunityResource
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CommunityResource
    {
        return $this->transactionManager->transactional(function () use ($data) {
            Assert::isInstanceOf($data, CommunityResource::class);

            $community = $this->communityRepo->ofId($data->id); //community cannot be null because we passed through CommunityItemProvider
            $community->fields = $this->fieldService->upsertFields($community, $data->fields);

            return CommunityResource::fromModel($community);
        });
    }
}