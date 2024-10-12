<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity()]
class Agent
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null;

    #[ORM\Column]
    public string $name;

    #[ORM\Column]
    public string $apiKey;

    #[ORM\OneToMany(targetEntity: Field::class, mappedBy: 'agent')]
    public Collection $fields;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
