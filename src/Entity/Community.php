<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity()]
class Community
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null;

    /**
     * @var ArrayCollection|Field[]
     */
    #[ORM\OneToMany(targetEntity: Field::class, mappedBy: 'community')]
    public Collection $fields;

    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'replaces')]
    public Collection $replacedBy;

    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'replacedBy')]
    public Collection $replaces;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    public ?Community $parent = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    public Collection $children;

    #[ORM\ManyToMany(targetEntity: Place::class, mappedBy: 'parentCommunities')]
    public Collection $placeChildren;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->replacedBy = new ArrayCollection();
        $this->replaces = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->placeChildren = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
