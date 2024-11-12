<?php

declare(strict_types=1);

namespace App\Community\Domain\Repository;

use App\Community\Domain\Model\Community;
use App\Shared\Domain\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<Community>
 */
interface CommunityRepositoryInterface extends RepositoryInterface
{
    public function ofId(string $communityid): ?Community;

    public function add(Community $community): void;

    public function withType(string $type): static;

    public function withWikidataId(int $wikidataId): static;
}