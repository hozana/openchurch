<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Infrastructure\Doctrine;

use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Shared\Infrastructure\Doctrine\DoctrineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Uid\Uuid;

/**
 * @extends DoctrineRepository<Community>
 */
final class DoctrineCommunityRepository extends DoctrineRepository implements CommunityRepositoryInterface
{
    private const ENTITY_CLASS = Community::class;
    private const ALIAS = 'community';

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }

    public function ofId(Uuid $communityId): ?Community
    {
        return $this->em->find(self::ENTITY_CLASS, $communityId);
    }

    public function add(Community $community): void
    {
        $this->em->persist($community);
    }

    public function addSelectField(): static
    {
        return $this->join(self::ALIAS, 'fields', 'fields')
            ->addSelect('fields');
    }

    /**
     * @param array<Uuid> $ids
     */
    public function ofIds(array $ids): static
    {
        if (!$ids || 0 === count($ids)) {
            return $this;
        }

        return
            $this->filter(static function (QueryBuilder $qb) use ($ids): void {
                $qb->addSelect('FIELD(community.id, :ids) AS HIDDEN orderField')
                    ->andWhere('community.id IN (:ids)')
                    ->setParameter('ids', array_map(fn (Uuid $id) => $id->toBinary(), $ids))
                    ->addOrderBy('orderField');
            });
    }

    public function withType(?string $value): static
    {
        if (!$value) {
            return $this;
        }

        return
            $this->filter(static function (QueryBuilder $qb) use ($value): void {
                $qb->andWhere("
                        EXISTS (SELECT 1 FROM App\Field\Domain\Model\Field f_type
                        WHERE f_type.community = community
                        AND f_type.name = 'type' AND f_type.stringVal = :valueType)
                    ")
                    ->setParameter('valueType', $value);
            });
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
                        WHERE f_wikidata.community = community
                        AND f_wikidata.name = 'wikidataId' AND f_wikidata.intVal = :valueWikidata)
                    ")
                    ->setParameter('valueWikidata', $value);
            });
    }

    public function withWikidataIds(?array $wikidataIds): static
    {
        if (0 === count($wikidataIds)) {
            return $this;
        }

        return
            $this->filter(static function (QueryBuilder $qb) use ($wikidataIds): void {
                $qb->andWhere("
                        EXISTS (SELECT 1 FROM App\Field\Domain\Model\Field f_wikidata
                        WHERE f_wikidata.community = community
                        AND f_wikidata.name = 'wikidataId' AND f_wikidata.intVal IN(:valueWikidataIds))
                    ")
                    ->setParameter('valueWikidataIds', $wikidataIds);
            });
    }

    public function withParentCommunityId(?Uuid $parentId): static
    {
        if (!$parentId) {
            return $this;
        }

        return
            $this->filter(static function (QueryBuilder $qb) use ($parentId): void {
                $qb->andWhere("
                        EXISTS (SELECT 1 FROM App\Field\Domain\Model\Field f_community_parent_id
                        WHERE f_community_parent_id.community = community
                        AND f_community_parent_id.name = 'parentCommunityId' AND IDENTITY(f_community_parent_id.communityVal) = :valueParentCommunity)
                    ")
                    ->setParameter('valueParentCommunity', $parentId->toBinary());
            });
    }

    public function sortByName(): static
    {
        return $this->sort(static function (QueryBuilder $qb): void {
            $qb->leftJoin('community.fields', 'sort_name_fields', Join::WITH, 'sort_name_fields.name = :name')
                ->setParameter('name', 'name')
                ->addOrderBy('sort_name_fields.stringVal', 'ASC');
        });
    }
}
