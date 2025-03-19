<?php

declare(strict_types=1);

namespace App\Shared\Domain\Repository;

use Doctrine\Common\Collections\Collection;
use Iterator;
use IteratorAggregate;

/**
 * @template T of object
 *
 * @extends \IteratorAggregate<array-key, T>
 */
interface RepositoryInterface extends IteratorAggregate, \Countable
{
    /**
     * @return Iterator<T>
     */
    public function getIterator(): Iterator;

    public function count(): int;

    /**
     * @return PaginatorInterface<T>|null
     */
    public function paginator(): ?PaginatorInterface;

    public function withPagination(int $page, int $itemsPerPage): static;

    public function withoutPagination(): static;

    public function clear(): void;

    public function detach(object $entity): void;

    public function remove(object $entity): void;

    /**
     * @return Collection<int, T>
     */
    public function asCollection(): Collection;
}
