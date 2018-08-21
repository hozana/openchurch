<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class DepartementRepository extends EntityRepository
{
    public function count(array $criteria)
    {
        $qb = $this->createQueryBuilder('d');

        return $qb
            ->select('count(d.id)')
            ->getQuery()
            ->useQueryCache(true)
            ->useResultCache(true, 31536000) // one year
            ->getSingleScalarResult();
    }
}
