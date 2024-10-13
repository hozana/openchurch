<?php

namespace App\Entity;

use App\Helper\Trait\Timestampable;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table]
class Place
{
    use Timestampable;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null;

    /**
     * @var ArrayCollection|Field[]
     */
    #[ORM\OneToMany(targetEntity: Field::class, mappedBy: 'place')]
    public Collection $fields;

    /**
     * @var ArrayCollection|Field[]
     */
    #[ORM\OneToMany(targetEntity: Field::class, mappedBy: 'placeVal')]
    public Collection $fieldsAsPlaceVal;

    /**
     * @var ArrayCollection|Field[]
     */
    #[ORM\ManyToMany(targetEntity: Field::class, mappedBy: 'placesVal')]
    public Collection $fieldsAsPlacesVal;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->fields = new ArrayCollection();
        $this->fieldsAsPlaceVal = new ArrayCollection();
        $this->fieldsAsPlacesVal = new ArrayCollection();
    }

    /**
     * @return Collection|Field[]
     */
    public function getFieldsByName(PlaceFieldName $name): Collection
    {
        return $this->fields
            ->filter(fn (Field $field) => $field->name === $name->value);
    }

    public function getFieldByNameAndAgent(PlaceFieldName $name, Agent $agent): ?Field
    {
        return $this->getFieldsByName($name)
            ->filter(fn (Field $field) => $field->agent === $agent)
            ->first() ?: null;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
