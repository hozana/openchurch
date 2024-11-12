<?php

declare(strict_types=1);

namespace App\Community\Infrastructure\Doctrine;

use App\Community\Domain\Model\Community;
use App\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Field\Domain\Enum\FieldCommunity;
use App\Shared\Infrastructure\Doctrine\DoctrineRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

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
        $this->join("fields", "fields");
    }

    public function ofId(string $communityId): ?Community
    {
        return $this->em->find(self::ENTITY_CLASS, $communityId);
    }

    public function add(Community $community): void
    {
        $this->em->persist($community);
    }

    public function withType(?string $value): static
    {
        if (!$value) return $this;
        return 
            $this->filter(static function (QueryBuilder $qb) use ($value): void {
                $qb->andWhere("
                        EXISTS (SELECT 1 FROM App\Field\Domain\Model\Field f_type
                        WHERE f_type.community = community
                        AND f_type.name = 'type' AND f_type.stringVal = :valueType)
                    ")
                    ->setParameter("valueType", $value);
        });
    }

    public function withWikidataId(?int $value): static
    {
        if (!$value) return $this;
        return 
            $this->filter(static function (QueryBuilder $qb) use ($value): void {
                $qb->andWhere("
                        EXISTS (SELECT 1 FROM App\Field\Domain\Model\Field f_wikidata
                        WHERE f_wikidata.community = community
                        AND f_wikidata.name = 'wikidataId' AND f_wikidata.intVal = :valueWikidata)
                    ")
                    ->setParameter("valueWikidata", $value);
        });
    }

    /**
     * @return Collection|Field[]
     */
    public function getFieldsByName(FieldCommunity $name): Collection
    {
        return $this->fields
            ->filter(fn (Field $field) => $field->name === $name->value);
    }

    public function getFieldByNameAndAgent(FieldCommunity $name, Agent $agent): ?Field
    {
        return $this->getFieldsByName($name)
            ->filter(fn (Field $field) => $field->agent === $agent)
            ->first() ?: null;
    }
}