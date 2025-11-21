<?php

declare(strict_types=1);

namespace App\FieldHolder\Place\Infrastructure\Doctrine;

use App\FieldHolder\Place\Domain\Model\Place;
use App\FieldHolder\Place\Domain\Repository\PlaceRepositoryInterface;
use App\Shared\Infrastructure\Doctrine\DoctrineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<Place>
 */
final class DoctrinePlaceRepository extends DoctrineRepository implements PlaceRepositoryInterface
{
    private const string ENTITY_CLASS = Place::class;
    private const string ALIAS = 'place';

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
        if ([] === $ids) {
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

    /**
     * @param array<int> $wikidataIds
     */
    public function withWikidataIds(?array $wikidataIds): static
    {
        if (0 === count($wikidataIds)) {
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

    public function withParentCommunityId(?Uuid $parentId): static
    {
        if (!$parentId instanceof Uuid) {
            return $this;
        }

        return
            $this->filter(static function (QueryBuilder $qb) use ($parentId): void {
                $qb->andWhere("
                        EXISTS (
                            SELECT 1 FROM App\Field\Domain\Model\Field f_community_parent_id
                            JOIN f_community_parent_id.communitiesVal communities
                            WHERE f_community_parent_id.place = place AND
                            f_community_parent_id.name = 'parentCommunities' AND
                            communities.id = :valueParentCommunity
                        )
                    ")
                    ->setParameter('valueParentCommunity', $parentId->toBinary());
            });
    }
}
