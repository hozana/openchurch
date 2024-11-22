<?php

declare(strict_types=1);

namespace App\Community\Domain\Repository;

use App\Community\Domain\Model\Community;
use App\Shared\Domain\Repository\RepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends RepositoryInterface<Community>
 */
interface CommunityRepositoryInterface extends RepositoryInterface
{
    public function ofId(Uuid $communityid): ?Community;

    /** @param string[] $communityid */
    public function ofIds(array $ids): static;

    public function addSelectField(): static;

    public function add(Community $community): void;

    public function withType(string $type): static;

    public function withWikidataId(int $wikidataId): static;
}