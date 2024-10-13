<?php

namespace App\Repository;

use App\Entity\Agent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AgentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Agent::class);
    }

    public function findAgentNameByApiKey(string $apiKey): ?string
    {
        $row = $this->createQueryBuilder('agent')
            ->select('agent.name')
            ->where('agent.apiKey = :apiKey')
            ->setParameter('apiKey', $apiKey)
            ->getQuery()
            // Cache result for 60 seconds
            ->enableResultCache(60)
            ->getOneOrNullResult();

        return $row['name'] ?? null;
    }
}
