<?php

namespace App\Controller\Api;

use App\Entity\Agent;
use App\Entity\Community;
use App\Entity\CommunityFieldName;
use App\Entity\Field;
use App\Entity\Place;
use App\Entity\PlaceFieldName;
use App\Model\Request\FieldMutations;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

class FieldController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LockFactory $lockFactory,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/{type}/{id}/fields', name: 'api_field', requirements: [
        'id' => '.+',
        'type' => '(places|communities)',
    ], methods: ['PATCH'])]
    public function setFields(string $type, string $id, Request $request): JsonResponse
    {
        assert(in_array($type, ['places', 'communities']));

        $entityClass = match($type) {
            'places' => Place::class,
            'communities' => Community::class,
        };
        $entity = $this->em->getRepository($entityClass)->find($id);
        if (!$entity) {
            throw new NotFoundHttpException();
        }
        assert($entity instanceof Place || $entity instanceof Community);

        // Lock for writing
        $lock = $this->lockFactory->createLock("$entityClass/$id");
        $lock->acquire(true);

        // TODO find logged in agent
        $agent = $this->em->find(Agent::class, '0192827e-f437-798e-82a1-7b91c2a2d1c3');

        try {
            try {
                $body = $this->serializer->deserialize($request->getContent(), FieldMutations::class, 'json');
            } catch (NotEncodableValueException) {
                throw new BadRequestHttpException('Body malformed');
            }

            foreach ($body->fields as $mutation) {
                $name = $mutation->name;
                $nameEnum = match($entityClass) {
                    Place::class => PlaceFieldName::tryFrom($name),
                    Community::class => CommunityFieldName::tryFrom($name),
                };
                if ($nameEnum === null) {
                    throw new BadRequestHttpException("Field $name: invalid field name");
                }

                $field = $entity->getFieldByNameAndAgent($nameEnum, $agent);
                if (!$field) {
                    $field = new Field();
                    $field->agent = $agent;
                    $this->em->persist($field);
                }

                if ($entityClass === Community::class) {
                    $field->community = $entity;
                } else {
                    $field->place = $entity;
                }
                $field->name = $mutation->name;
                $field->value = $mutation->value;
                $field->source = $mutation->source;
                $field->reliability = $mutation->reliability;
                $field->explanation = $mutation->explanation;
                $field->touch();

                $violations = $this->validator->validate($field);
                if (count($violations) > 0) {
                    $messages = [];
                    foreach ($violations as $violation) {
                        $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
                    }
                    throw new BadRequestHttpException("Field $name: validation failed : ".implode(' ; ', $messages));
                }

                $field->applyValue();
            }

            $entity->touch();
            $this->em->flush();
        } finally {
            $lock->release();
        }

        return $this->json([]);
    }
}
