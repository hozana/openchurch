<?php

declare(strict_types=1);

namespace App\Community\Infrastructure\Doctrine;

use App\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Entity\Community;
use App\Shared\Infrastructure\Doctrine\DoctrineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends DoctrineRepository<Community>
 */
final class DoctrineCommunityRepository extends DoctrineRepository implements CommunityRepositoryInterface
{
    private const ENTITY_CLASS = Community::class;
    private const ALIAS = 'community';
    private bool $hasJoined = false;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }

    private function joinField() {
        if (!$this->hasJoined) {
            $this->query()->join('community.fields', 'fields');
            $this->hasJoined = true;
        }
    }

    public function withType(string $value): static
    {
        return 
            $this->filter(static function (QueryBuilder $qb) use ($value): void {
                $qb->join('community.fields', 'fields')
                    ->andWhere('fields.name = :type AND fields.stringVal = :value')
                    ->setParameter("type", 'type')
                    ->setParameter("value", $value);
        });
    }
}