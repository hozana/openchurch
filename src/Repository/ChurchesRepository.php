<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class ChurchesRepository extends EntityRepository
{
    public function count(array $criteria)
    {
        $qb = $this->createQueryBuilder('c');

        return $qb
            ->select('count(c.church_id)')
            ->getQuery()
            ->useQueryCache(true)
            ->useResultCache(true, 86400) // one day
            ->getSingleScalarResult();
    }
}
