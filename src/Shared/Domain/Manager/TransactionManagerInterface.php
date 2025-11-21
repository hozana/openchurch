<?php

declare(strict_types=1);

namespace App\Shared\Domain\Manager;

interface TransactionManagerInterface
{
    public function transactional(callable $operation): mixed;
}
