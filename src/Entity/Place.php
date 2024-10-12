<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity()]
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

    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'replaces')]
    public Collection $replacedBy;

    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'replacedBy')]
    public Collection $replaces;

    #[ORM\ManyToOne(targetEntity: Community::class, inversedBy: 'placeChildren')]
    public Collection $parentCommunities;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->replacedBy = new ArrayCollection();
        $this->replaces = new ArrayCollection();
        $this->parentCommunities = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
