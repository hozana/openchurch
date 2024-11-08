<?php

declare(strict_types=1);

namespace App\Community\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Agent\Domain\Model\Agent;
use App\Community\Domain\Model\Community;
use App\Community\Infrastructure\ApiPlatform\Payload\CreateCommunityPayload;
use App\Community\Infrastructure\ApiPlatform\Resource\CommunityResource;
use App\Field\Domain\Model\Field;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<null>
 */
final class CreateCommunityProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {
    }

    /**
     * @param CreateCommunityPayload $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CommunityResource
    {
        Assert::isInstanceOf($data, CreateCommunityPayload::class);

        $repo = $this->em->getRepository(Agent::class);
        $community = new Community();
        $this->em->persist($community);

        $insertedFields = new ArrayCollection();
        foreach ($data->fields as $field) {
            $entityField = new Field();
            $entityField->name = $field->name;
            $entityField->value = $field->value;
            $entityField->reliability = $field->reliability;
            $entityField->source = $field->source;
            $entityField->explanation = $field->explanation;
            $entityField->agent = $repo->find("01928276-75e8-7afc-832c-6b8101951a13");
            $entityField->engine = $field->engine;
            $entityField->community = $community;

            $violations = $this->validator->validate($entityField);
            if (count($violations) > 0) {
                throw new ValidationException($violations);
            }

            $this->em->persist($entityField);
            $insertedFileds[] = $entityField;
        }

        $this->em->flush();

        return new CommunityResource(
            $community->id,
            $insertedFields
        );
    }
}