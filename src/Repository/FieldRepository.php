<?php

namespace App\Repository;

use App\Entity\CommunityFieldName;
use App\Entity\Field;
use App\Entity\PlaceFieldName;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class FieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Field::class);
    }

    /**
     * Checks if the specific field exists across the database. If it is, will return the UUID
     */
    public function exists(PlaceFieldName|CommunityFieldName $fieldName, mixed $fieldValue): string|null
    {
        $qb = $this->createQueryBuilder('field');

        return $qb
            ->select('COALESCE(IDENTITY(field.community), IDENTITY(field.place)) as attachedToId')
            ->where($this->whereFieldEquals(
                $qb,
                $fieldName,
                $fieldValue,
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function whereFieldEquals(QueryBuilder $qb, PlaceFieldName|CommunityFieldName $fieldName, mixed $fieldValue, string $alias = 'field'): Comparison
    {
        $propertyName = Field::getPropertyName($fieldName);

        $parameterName = "{$alias}_value";
        $qb->setParameter($parameterName, $fieldValue);
        return $qb->expr()->eq("$alias.$propertyName", ":$parameterName");
    }
}
