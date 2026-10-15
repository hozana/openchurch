<?php

declare(strict_types=1);

namespace App\Admin\Application;

use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Model\Field;
use Symfony\Component\Uid\Uuid;

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

    /**
     * The payload FieldService writes, on behalf of an admin: a human value of the highest reliability.
     */
    public function toPayload(string $source): Field
    {
        $payload = new Field();
        $payload->name = $this->name->value;
        $payload->value = $this->value;
        $payload->engine = FieldEngine::HUMAN;
        $payload->reliability = FieldReliability::HIGH;
        $payload->source = $source;
        $payload->explanation = $this->explanation;

        return $payload;
    }

    /**
     * The ids of the related communities, in their canonical (lowercase) form and without duplicates.
     *
     * @return list<string>
     */
    public function relatedIds(): array
    {
        return array_values(array_unique(array_map(
            static fn (string $id): string => Uuid::isValid($id) ? Uuid::fromString($id)->toRfc4122() : $id,
            array_filter((array) $this->value, is_string(...)),
        )));
    }
}
