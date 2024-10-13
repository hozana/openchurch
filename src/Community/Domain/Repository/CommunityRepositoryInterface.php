<?php

declare(strict_types=1);

namespace App\Community\Domain\Repository;

use App\Entity\Community;
use App\Shared\Domain\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<Community>
 */
interface CommunityRepositoryInterface extends RepositoryInterface
{
    public function withType(string $type): static;

}