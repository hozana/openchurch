<?php

declare(strict_types=1);

namespace App\Community\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Community\Domain\Exception\CommunityNotFoundException;
use App\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Community\Infrastructure\ApiPlatform\Payload\UpdateCommunityPayload;
use App\Community\Infrastructure\ApiPlatform\Resource\CommunityResource;
use App\Field\Application\FieldService;
use App\Shared\Domain\Manager\TransactionManagerInterface;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<UpdateCommunityPayload, CommunityResource>
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
     * @param UpdateCommunityPayload $data
     * @return CommunityResource
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CommunityResource
    {
        return $this->transactionManager->transactional(function () use ($data) {
            Assert::isInstanceOf($data, UpdateCommunityPayload::class);

            $community = $this->communityRepo->ofId(Uuid::fromString(($data->id)));
            if (!$community) {
                throw new CommunityNotFoundException($data->id);
            }
            $fields = $this->fieldService->upsertFields($community, $data->fields);

            return new CommunityResource(
                $community->id,
                $fields
            );
        });
    }
}