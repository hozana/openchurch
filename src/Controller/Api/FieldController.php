<?php

namespace App\Controller\Api;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Entity\Agent;
use App\Entity\Community;
use App\Entity\CommunityFieldName;
use App\Entity\Field;
use App\Entity\Place;
use App\Entity\PlaceFieldName;
use App\Model\Request\FieldMutations;
use App\Repository\FieldRepository;
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
use function Symfony\Component\String\s;

class FieldController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FieldRepository $fieldRepo,
        private readonly LockFactory $lockFactory,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/{type}', name: 'add', requirements: [
        'type' => '(places|communities)',
    ], methods: ['POST'])]
    #[Route('/{type}/{id}', name: 'patch', requirements: [
        'id' => '.+',
        'type' => '(places|communities)',
    ], methods: ['PATCH'])]
    public function setFields(string $type, ?string $id, Request $request): JsonResponse
    {
        assert(in_array($type, ['places', 'communities']));

        $entityClass = match($type) {
            'places' => Place::class,
            'communities' => Community::class,
        };

        if ($request->attributes->get('_route') === 'add') {
            $entity = new $entityClass();
            $this->em->persist($entity);
        } else {
            $entity = $this->em->getRepository($entityClass)->find($id);
            if (!$entity) {
                throw new NotFoundHttpException('entity-not-found');
            }
        }
        assert($entity instanceof Place || $entity instanceof Community);

        // Lock specified resource for writing
        $lock = $this->lockFactory->createLock("$entityClass/$id");
        $lock->acquire(true);

        $agent = $this->getUser();
        assert($agent instanceof Agent);

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

                // Transform uuid into Community|Place and uuid[] into Community[]|Place[]
                $value = $this->maybeTransformEntities($nameEnum, $mutation->value);

                if ($entityClass === Community::class) {
                    $field->community = $entity;
                } else {
                    $field->place = $entity;
                }
                $field->name = $mutation->name;
                $field->value = $value;
                $field->engine = $mutation->engine;
                $field->reliability = $mutation->reliability;
                $field->source = $mutation->source;
                $field->explanation = $mutation->explanation;
                $field->touch();

                // Unique constraints validation (TODO use custom Assert instead)
                if ($field->value !== null
                    && in_array($field->name, Field::UNIQUE_CONSTRAINTS, true)
                    && (null !== $attachedToId = $this->fieldRepo->exists($nameEnum, $field->value))
                    && $attachedToId !== $entity->id) {
                    // TODO Malformed UTF-8 characters, possibly incorrectly encoded
                    $attachedToIdStr = '(unknown)'; // s($attachedToId)->toString();
                    throw new BadRequestHttpException("Found duplicate for field $field->name: see entity $attachedToIdStr");
                }

                $violations = $this->validator->validate($field);
                if (count($violations) > 0) {
                    throw new ValidationException($violations);
                }

                $field->applyValue();
            }

            $entity->touch();
            $this->em->flush();
        } finally {
            $lock->release();
        }

        return $this->json([
            'id' => $entity->id,
        ]);
    }

    /**
     * @param CommunityFieldName|PlaceFieldName $field
     * @param string|array $value
     * @return Community|Community[]|Place|Place[]
     */
    private function maybeTransformEntities(CommunityFieldName|PlaceFieldName $nameEnum, mixed $value): mixed
    {
        $type = $nameEnum->getType();
        if (!in_array($type, [
            'Community',
            'Community[]',
            'Place',
            'Place[]',
        ], true)) {
            return $value;
        }

        if ($value === null) {
            return null;
        }
        if ($value === []) {
            return [];
        }

        $targetEntityClassName = match(s($type)->trimSuffix('[]')->toString()) {
            'Community' => Community::class,
            'Place' => Place::class,
        };
        $repo = $this->em->getRepository($targetEntityClassName);

        if (str_ends_with($type, '[]')) {
            // That's an array
            if (!is_array($value)) {
                throw new BadRequestHttpException($nameEnum->value.": should be an array");
            }
            assert(is_array($value));

            //$instances = $repo->findBy(['id' => $value]);: does not work
            $instances = array_map(fn (string $id) => $repo->find($id), $value);
            $instances = array_filter($instances);

            if (count($instances) !== count($value)) {
                throw new BadRequestHttpException($nameEnum->value.": Could not find some values from provided IDs");
            }

            return $instances;
        } else {
            // That's an object
            assert(is_string($value));
            $instance = $repo->find($value);

            if (!$instance) {
                throw new BadRequestHttpException($nameEnum->value.": Could not find value from provided ID");
            }

            return $instance;
        }
    }
}
