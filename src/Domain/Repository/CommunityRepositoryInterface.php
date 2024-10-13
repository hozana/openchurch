<?php

declare(strict_types=1);

namespace App\BookStore\Domain\Repository;

use App\BookStore\Domain\Model\Book;
use App\BookStore\Domain\ValueObject\Author;
use App\BookStore\Domain\ValueObject\BookId;
use App\Entity\Community;
use App\Shared\Domain\Repository\RepositoryInterface;
use Symfony\Component\Uid\UuidV7;

/**
 * @extends RepositoryInterface<Book>
 */
interface CommunityRepositoryInterface extends RepositoryInterface
{
    public function ofId(UuidV7 $id): ?Community;

    public function withType(string $author): static;
}