<?php

namespace App\Shared\Infrastructure\Doctrine;

use App\Shared\Domain\Manager\TransactionManagerInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTransactionManager implements TransactionManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function transactional(callable $operation): mixed
    {
        return $this->entityManager->wrapInTransaction($operation);
    }
}