<?php

declare(strict_types=1);

namespace App\Community\Infrastructure\Doctrine;

use App\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Entity\Community;
use App\Entity\CommunityFieldName;
use App\Entity\Field;
use App\Shared\Infrastructure\Doctrine\DoctrineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends DoctrineRepository<Community>
 */
final class DoctrineCommunityRepository extends DoctrineRepository implements CommunityRepositoryInterface
{
    private const ENTITY_CLASS = Community::class;
    private const ALIAS = 'community';
    private bool $hasJoined = false;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }

    private function joinField() {
        if (!$this->hasJoined) {
            $this->query()->join('community.fields', 'fields');
            $this->hasJoined = true;
        }
    }

    public function withType(string $value): static
    {
        $this->joinField();
        return $this->filter(static function (QueryBuilder $qb) use ($value): void {
            $propertyName = 'type';
            $qb->expr()->eq("fields.type", ":value");
            $qb->setParameter("value", $fieldValue);
            
            $qb->join('community.fields', 'field_type', Join::WITH, CommunityFieldName::NAME->value . 'AND fields.value = :value')
                ->setParameter('type', 'type')
                ->setParameter('value', $value);
        });
    }
}