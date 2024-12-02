<?php

namespace App\Agent\Infrastructure\Doctrine;

use App\Agent\Domain\Model\Agent;
use App\Agent\Domain\Repository\AgentRepositoryInterface;
use App\Shared\Infrastructure\Doctrine\DoctrineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class DoctrineAgentRepository extends DoctrineRepository implements AgentRepositoryInterface
{
    private const ENTITY_CLASS = Agent::class;
    private const ALIAS = 'agent';

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }

    public function findAgentNameByApiKey(string $apiKey): ?string
    {
        $qb = $this->query();

        $row = $qb->select('agent.name')
            ->where('agent.apiKey = :apiKey')
            ->setParameter('apiKey', $apiKey)
            ->getQuery()
            // Cache result for 60 seconds
            ->enableResultCache(60)
            ->getOneOrNullResult();

        return $row['name'] ?? null;
    }
}
