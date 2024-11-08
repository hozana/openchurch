<?php

declare(strict_types=1);

namespace App\Place\Infrastructure\ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Agent\Domain\Model\Agent;
use App\ApiResource\Place\CreatePlaceInput;
use App\Field\Domain\Model\Field;
use App\Place\Domain\Model\Place;
use App\Place\Infrastructure\ApiPlatform\Payload\CreatePlacePayload;
use App\Place\Infrastructure\ApiPlatform\Resource\PlaceResource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<null>
 */
final class CreatePlaceProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {
    }

    /**
     * @param CreatePlaceInput $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PlaceResource
    {
        Assert::isInstanceOf($data, CreatePlacePayload::class);

        $repo = $this->em->getRepository(Agent::class);
        $place = new Place();
        $this->em->persist($place);

        foreach ($data->fields as $field) {
            $entityField = new Field();
            $entityField->name = $field->name;
            $entityField->value = $field->value;
            $entityField->reliability = $field->reliability;
            $entityField->source = $field->source;
            $entityField->explanation = $field->explanation;
            $entityField->agent = $repo->find("01928276-75e8-7afc-832c-6b8101951a13");
            $entityField->place = $place;

            $violations = $this->validator->validate($entityField);
            if (count($violations) > 0) {
                // Gérer les violations, par exemple en lançant une exception
                throw new ValidationException($violations);
            }

            $this->em->persist($entityField);
        }

        $this->em->flush();

        return new PlaceResource($place->id);
    }
}