<?php

namespace App\Repository;

use App\Entity\Parish;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Parish|null find($id, $lockMode = null, $lockVersion = null)
 * @method Parish|null findOneBy(array $criteria, array $orderBy = null)
 * @method Parish[]    findAll()
 * @method Parish[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParishRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parish::class);
    }

    public function countAll()
    {
        return $this->createQueryBuilder('c')
            ->select('count(c.id)')
            ->getQuery()
            ->useQueryCache(true)
            ->enableResultCache(86400) // one day
            ->getSingleScalarResult();
    }
}
