<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Field\Application\FieldService;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Community\Infrastructure\ApiPlatform\Resource\CommunityResource;
use App\Shared\Domain\Manager\TransactionManagerInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<CommunityResource, CommunityResource>
 */
final readonly class CreateCommunityProcessor implements ProcessorInterface
{
    public function __construct(
        private CommunityRepositoryInterface $communityRepo,
        private TransactionManagerInterface $transactionManager,
        private FieldService $fieldService,
    ) {
    }

    /**
     * @param CommunityResource $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CommunityResource
    {
        return $this->transactionManager->transactional(function () use ($data) {
            Assert::isInstanceOf($data, CommunityResource::class);

            $community = new Community();
            $this->communityRepo->add($community);

            $this->fieldService->upsertFields($community, $data->fields);

            return CommunityResource::fromModel(
                $community,
            );
        });
    }
}
