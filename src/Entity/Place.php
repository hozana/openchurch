<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table]
class Place
{
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
        $this->fields = new ArrayCollection();
        $this->fieldsAsPlaceVal = new ArrayCollection();
        $this->fieldsAsPlacesVal = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
