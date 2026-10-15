<?php

declare(strict_types=1);

namespace App\Admin\Application;

use DateTimeImmutable;

/**
 * The value an agent gave to a field, as displayed in the admin.
 */
final readonly class AgentValue
{
    public function __construct(
        public string $agentName,
        public string $engine,
        public string $reliability,
        public string $value,
        public ?string $source,
        public ?string $explanation,
        public ?DateTimeImmutable $updatedAt,
        /** Whether it's the value of the admin agent */
        public bool $isAdmin,
        public bool $isRetained,
    ) {
    }
}
