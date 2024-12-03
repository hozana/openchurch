<?php

declare(strict_types=1);

namespace App\Place\Infrastructure\Doctrine;

use App\Place\Domain\Model\Place;
use App\Place\Domain\Repository\PlaceRepositoryInterface;
use App\Shared\Infrastructure\Doctrine\DoctrineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<Place>
 */
final class DoctrinePlaceRepository extends DoctrineRepository implements PlaceRepositoryInterface
{
    private const ENTITY_CLASS = Place::class;
    private const ALIAS = 'place';

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
        $this->join(self::ALIAS, 'fields', 'fields');
    }

    public function ofId(Uuid $placeId): ?Place
    {
        return $this->em->find(self::ENTITY_CLASS, $placeId);
    }

    public function add(Place $place): void
    {
        $this->em->persist($place);
    }
}
