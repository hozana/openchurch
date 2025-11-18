<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Domain\Repository;

use App\FieldHolder\Community\Domain\Model\Community;
use App\Shared\Domain\Repository\RepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends RepositoryInterface<Community>
 */
interface CommunityRepositoryInterface extends RepositoryInterface
{
    public function ofId(Uuid $communityid): ?Community;

    /**
     * @param array<Uuid> $ids
     */
    public function ofIds(array $ids): static;

    public function addSelectField(): static;

    public function add(Community $community): void;

    public function withType(string $type): static;

    public function withWikidataId(int $wikidataId): static;

    /**
     * @param array<int> $wikidataIds
     */
    public function withWikidataIds(array $wikidataIds): static;

    public function withParentCommunityId(Uuid $parentId): static;

    public function withContactZipcode(string $contactZipcode): static;

    public function sortByName(): static;
}
