<?php

declare(strict_types=1);

namespace App\FieldHolder\Place\Infrastructure\Doctrine;

use App\FieldHolder\Place\Domain\Model\Place;
use App\FieldHolder\Place\Domain\Repository\PlaceRepositoryInterface;
use App\Shared\Infrastructure\Doctrine\DoctrineRepository;
use Doctrine\ORM\QueryBuilder;
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
    }

    public function ofId(Uuid $placeId): ?Place
    {
        return $this->em->find(self::ENTITY_CLASS, $placeId);
    }

        /**
     * @param array<Uuid> $ids
     */
    public function ofIds(array $ids): static
    {
        dd('je passe');
        if (!$ids || 0 === count($ids)) {
            return $this;
        }

        return
            $this->filter(static function (QueryBuilder $qb) use ($ids): void {
                $qb->andWhere('place.id IN (:ids)')
                    ->setParameter('ids', array_map(fn (Uuid $id) => $id->toBinary(), $ids));
            });
    }

    public function add(Place $place): void
    {
        $this->em->persist($place);
    }

    public function addSelectField(): static
    {
        return $this->join(self::ALIAS, 'fields', 'fields')
            ->addSelect('fields');
    }

    public function withWikidataId(?int $value): static
    {
        if (!$value) {
            return $this;
        }

        return
            $this->filter(static function (QueryBuilder $qb) use ($value): void {
                $qb->andWhere("
                        EXISTS (SELECT 1 FROM App\Field\Domain\Model\Field f_wikidata
                        WHERE f_wikidata.place = place
                        AND f_wikidata.name = 'wikidataId' AND f_wikidata.intVal = :valueWikidata)
                    ")
                    ->setParameter('valueWikidata', $value);
            });
    }

    public function withWikidataIds(?array $wikidataIds): static
    {
        if (count($wikidataIds) === 0) {
            return $this;
        }

        return
            $this->filter(static function (QueryBuilder $qb) use ($wikidataIds): void {
                $qb->andWhere("
                        EXISTS (SELECT 1 FROM App\Field\Domain\Model\Field f_wikidata
                        WHERE f_wikidata.place = place
                        AND f_wikidata.name = 'wikidataId' AND f_wikidata.intVal IN(:valueWikidataIds))
                    ")
                    ->setParameter('valueWikidataIds', $wikidataIds);
            });
    }
}
