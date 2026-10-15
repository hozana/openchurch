<?php

declare(strict_types=1);

namespace App\Admin\Application;

use App\Field\Domain\Enum\FieldCommunity;

final readonly class FieldOverview
{
    /**
     * @param list<AgentValue> $values     the retained value first
     * @param AdminFieldState  $adminState the value of the admin agent, which the edit form starts from
     */
    public function __construct(
        public FieldCommunity $name,
        public string $label,
        public bool $editable,
        public array $values,
        public AdminFieldState $adminState,
    ) {
    }

    public function hasAdminValue(): bool
    {
        return [] !== array_filter($this->values, static fn (AgentValue $value): bool => $value->isAdmin);
    }
}
