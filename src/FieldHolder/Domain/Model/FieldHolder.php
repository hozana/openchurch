<?php

namespace App\FieldHolder\Domain\Model;

use App\Agent\Domain\Model\Agent;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldPlace;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Model\Field;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

class FieldHolder
{
    /**
     * @var Collection<int, Field>
     */
    #[ORM\OneToMany(targetEntity: Field::class, mappedBy: 'community')]
    public Collection $fields;

    /**
     * @return Collection<int, Field>
     */
    public function getFieldsByName(FieldCommunity|FieldPlace $name): Collection
    {
        return $this->fields
            ->filter(fn (Field $field) => $field->name === $name->value);
    }

    public function getMostTrustableFieldByName(FieldCommunity|FieldPlace $name): ?Field
    {
        $result = $this->getFieldsByName($name)->toArray();
        if (0 === count($result)) {
            return null;
        }

        usort($result, function (Field $a, Field $b) {
            return FieldReliability::compare($a->reliability, $b->reliability);
        });

        return $result[0];
    }

    public function getFieldByNameAndAgent(FieldCommunity|FieldPlace $name, Agent $agent): ?Field
    {
        return $this->getFieldsByName($name)
            ->filter(fn (Field $field) => $field->agent === $agent)
            ->first() ?: null;
    }
}