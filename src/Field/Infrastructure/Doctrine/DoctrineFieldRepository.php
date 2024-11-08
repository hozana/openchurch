<?php

namespace App\Field\Infrastructure\Doctrine;

use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldPlace;
use App\Field\Domain\Model\Field;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Field::class);
    }

    /**
     * Checks if the specific field exists across the database. If it is, will return the UUID
     */
    public function exists(FieldPlace|FieldCommunity $fieldName, mixed $fieldValue): string|null
    {
        $qb = $this->createQueryBuilder('field');

        $row = $qb
            ->select('COALESCE(IDENTITY(field.community), IDENTITY(field.place)) as attachedToId')
            ->where($this->whereFieldEquals(
                $qb,
                $fieldName,
                $fieldValue,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $row['attachedToId'] ?? null;
    }

    public function whereFieldEquals(QueryBuilder $qb, FieldPlace|FieldCommunity $fieldName, mixed $fieldValue, string $alias = 'field'): Comparison
    {
        $propertyName = Field::getPropertyName($fieldName);

        $parameterName = "{$alias}_value";
        $qb->setParameter($parameterName, $fieldValue);
        return $qb->expr()->eq("$alias.$propertyName", ":$parameterName");
    }
}
