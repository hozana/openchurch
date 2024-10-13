<?php

declare(strict_types=1);

namespace App\BookStore\Infrastructure\Doctrine;

use App\BookStore\Domain\Model\Book;
use App\BookStore\Domain\Repository\BookRepositoryInterface;
use App\BookStore\Domain\Repository\CommunityRepositoryInterface;
use App\BookStore\Domain\ValueObject\Author;
use App\BookStore\Domain\ValueObject\BookId;
use App\Entity\Community;
use App\Shared\Infrastructure\Doctrine\DoctrineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends DoctrineRepository<Book>
 */
final class DoctrineCommunityRepository extends DoctrineRepository implements CommunityRepositoryInterface
{
    private const ENTITY_CLASS = Community::class;
    private const ALIAS = 'community';

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }

    public function withType(string $value): static
    {
        return $this->filter(static function (QueryBuilder $qb) use ($value): void {
            $qb->andWhere('field.name = type AND field.value = :value')
                ->setParameter('value', $value);
        });
    }
}