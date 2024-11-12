<?php

declare(strict_types=1);

namespace App\Community\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Agent\Infrastructure\Doctrine\DoctrineAgentRepository;
use App\Community\Domain\Model\Community;
use App\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Community\Infrastructure\ApiPlatform\Payload\CreateCommunityPayload;
use App\Community\Infrastructure\ApiPlatform\Resource\CommunityResource;
use App\Field\Application\FieldService;
use App\Shared\Domain\Manager\TransactionManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<CreateCommunityPayload, CommunityResource>
 */
final class CreateCommunityProcessor implements ProcessorInterface
{
    public function __construct(
        private CommunityRepositoryInterface $communityRepo,
        private TransactionManagerInterface $transactionManager,
        private FieldService $fieldService,
    ) {
    }

    /**
     * @param CreateCommunityPayload $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CommunityResource
    {
        return $this->transactionManager->transactional(function () use ($data) {
            Assert::isInstanceOf($data, CreateCommunityPayload::class);

            $community = new Community();
            $this->communityRepo->add($community);

            $fields = $this->fieldService->upsertFields($community, $data->fields);
            return new CommunityResource(
                $community->id,
                $fields
            );
        });
    }
}