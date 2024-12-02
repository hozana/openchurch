<?php

declare(strict_types=1);

namespace App\Agent\Domain\Repository;

use App\Shared\Domain\Repository\RepositoryInterface;
use App\Agent\Domain\Model\Agent;

/**
 * @extends RepositoryInterface<Agent>
 */
interface AgentRepositoryInterface extends RepositoryInterface
{
    public function findAgentNameByApiKey(string $apiKey): ?string;
}