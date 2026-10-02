<?php

namespace App\FieldHolder;

use App\Agent\Domain\Model\Agent;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldPlace;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Model\Field;
use Doctrine\Common\Collections\Collection;

class FieldHolder
{
    /**
     * @var Collection<int, Field>
     */
    public Collection $fields;

    /**
     * @return Collection<int, Field>
     */
    public function getFieldsByName(FieldCommunity|FieldPlace $name): Collection
    {
        return $this->fields
            ->filter(static fn (Field $field) => $field->name === $name->value)
        ;
    }

    public function getMostTrustableFieldByName(FieldCommunity|FieldPlace $name): ?Field
    {
        $result = $this->getFieldsByName($name)->toArray();
        if (0 === count($result)) {
            return null;
        }

        // On equal reliability, a human correction wins over automated sources. Then the most recent
        // value wins.
        usort($result, static fn (Field $a, Field $b) => FieldReliability::compare($a->reliability, $b->reliability)
            ?: FieldEngine::compare($a->engine, $b->engine)
            ?: ($b->updatedAt ?? $b->createdAt) <=> ($a->updatedAt ?? $a->createdAt)
            ?: strcmp((string) $b->id?->toRfc4122(), (string) $a->id?->toRfc4122()));

        return $result[0];
    }

    public function getFieldByNameAndAgent(FieldCommunity|FieldPlace $name, Agent $agent): ?Field
    {
        return $this->getFieldsByName($name)
            ->filter(static fn (Field $field) => $field->agent->is($agent))
            ->first() ?: null;
    }
}
