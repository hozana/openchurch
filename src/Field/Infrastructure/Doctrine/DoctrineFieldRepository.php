<?php

namespace App\Field\Infrastructure\Doctrine;

use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldPlace;
use App\Field\Domain\Model\Field;
use App\Field\Domain\Repository\FieldRepositoryInterface;
use App\Shared\Infrastructure\Doctrine\DoctrineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Uid\Uuid;

class DoctrineFieldRepository extends DoctrineRepository implements FieldRepositoryInterface
{
    private const ENTITY_CLASS = Field::class;
    private const ALIAS = 'field';

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }

    public function add(Field $field): void
    {
        $this->em->persist($field);
    }

    /**
     * Checks if the specific field exists across the database. If it is, will return the UUID
     */
    public function exists(Uuid $id, FieldPlace|FieldCommunity $fieldName, mixed $fieldValue): string|null
    {
        $qb = $this->query();
        $row = $qb->select('COALESCE(IDENTITY(field.community), IDENTITY(field.place)) as attachedToId')
            ->where($this->whereFieldEquals(
                $qb,
                $fieldName,
                $fieldValue,
            ))
            ->andWhere($qb->expr()->eq('field.name', ':fieldName'))
            ->setParameter('fieldName', $fieldName)
            ->andWhere($qb->expr()->orX(
                $fieldName instanceof FieldCommunity ? $qb->expr()->neq('field.community', ':id') : $qb->expr()->neq('field.place', ':id'),
                $fieldName instanceof FieldCommunity ? $qb->expr()->isNull('field.community') : $qb->expr()->isNull('field.place'),
            ))
            ->setParameter('id', $id->toBinary())
            ->getQuery()
            ->getOneOrNullResult();

        return $row['attachedToId'] ?? null;
    }

    private function whereFieldEquals(QueryBuilder $qb, FieldPlace|FieldCommunity $fieldName, mixed $fieldValue, string $alias = 'field'): Comparison
    {
        $propertyName = Field::getPropertyName($fieldName);
        $parameterName = "{$alias}_value";

        $qb->setParameter($parameterName, $fieldValue);
        return $qb->expr()->eq("$alias.$propertyName", ":$parameterName");
    }
}
