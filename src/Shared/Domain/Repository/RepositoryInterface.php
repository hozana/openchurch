<?php

declare(strict_types=1);

namespace App\Shared\Domain\Repository;

use Doctrine\Common\Collections\Collection;

/**
 * @template T of object
 *
 * @extends \IteratorAggregate<array-key, T>
 */
interface RepositoryInterface extends \IteratorAggregate, \Countable
{
    /**
     * @return \Iterator<T>
     */
    public function getIterator(): \Iterator;

    public function count(): int;

    /**
     * @return PaginatorInterface<T>|null
     */
    public function paginator(): ?PaginatorInterface;

    /**
     * @return static<T>
     */
    public function withPagination(int $page, int $itemsPerPage): static;

    /**
     * @return static<T>
     */
    public function withoutPagination(): static;

    public function clear(): void;

    public function asCollection(): Collection;
}