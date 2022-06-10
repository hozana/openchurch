<?php

namespace App\Repository;

use App\Entity\Church;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Church|null find($id, $lockMode = null, $lockVersion = null)
 * @method Church|null findOneBy(array $criteria, array $orderBy = null)
 * @method Church[]    findAll()
 * @method Church[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChurchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Church::class);
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
