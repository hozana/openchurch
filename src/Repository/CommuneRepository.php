<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class CommuneRepository extends EntityRepository
{
    public function count(array $criteria)
    {
        $qb = $this->createQueryBuilder('c');

        return $qb
            ->select('count(c.id)')
            ->getQuery()
            ->useQueryCache(true)
            ->useResultCache(true, 604800) // one week
            ->getSingleScalarResult();
    }
}
