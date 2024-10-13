<?php

declare(strict_types=1);

namespace App\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\ApiResource\Community\CreateCommunityInput;
use App\ApiResource\CommunityResource;
use App\Entity\Agent;
use App\Entity\Community;
use App\Entity\Field;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<null>
 */
final readonly class CreateCommunityProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {
    }

    /**
     * @param CreateCommunityInput $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CommunityResource
    {
        Assert::isInstanceOf($data, CreateCommunityInput::class);

        $repo = $this->em->getRepository(Agent::class);
        $community = new Community();
        $this->em->persist($community);

        foreach ($data->fields as $field) {
            $entityField = new Field();
            $entityField->name = $field->name;
            $entityField->value = $field->value;
            $entityField->reliability = $field->reliability;
            $entityField->source = $field->source;
            $entityField->explanation = $field->explanation;
            $entityField->agent = $repo->find("01928276-75e8-7afc-832c-6b8101951a13");
            $entityField->community = $community;

            $violations = $this->validator->validate($entityField);
            if (count($violations) > 0) {
                // Gérer les violations, par exemple en lançant une exception
                throw new ValidationException($violations);
            }

            $this->em->persist($entityField);
        }

        $this->em->flush();

        return new CommunityResource($community->id);
    }
}