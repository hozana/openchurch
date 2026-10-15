<?php

declare(strict_types=1);

namespace App\Admin\Application;

use App\Field\Domain\Enum\FieldCommunity;

final readonly class AdminFieldInput
{
    /**
     * @param string|int|float|list<string>|null $value normalized value (see FieldValueNormalizer)
     */
    public function __construct(
        public FieldCommunity $name,
        public string|int|float|array|null $value,
        public ?string $explanation = null,
    ) {
    }

    public function state(): AdminFieldState
    {
        return new AdminFieldState($this->value, $this->explanation);
    }
}
