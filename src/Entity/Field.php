<?php

namespace App\Entity;

use App\Helper\Trait\Timestampable;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity()]
class Field
{
    use Timestampable;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Community::class, inversedBy: 'fields')]
    public ?Community $community = null;

    #[ORM\ManyToOne(targetEntity: Place::class, inversedBy: 'fields')]
    public ?Place $place = null;

    /**
     * Field name: not registered as enum
     *  - so that we can add more easily without a costly database migration ;
     *  - because communities and places doesn't share the same allowed values.
     *
     * @see CommunityFieldName
     * @see PlaceFieldName
     */
    #[ORM\Column]
    public string $name;

    #[ORM\Column(nullable: true)]
    public ?string $stringVal = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    public ?string $intVal = null;

    #[ORM\Column(type: 'float', nullable: true)]
    public ?string $floatVal = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    public ?string $datetimeVal = null;

    #[ORM\ManyToOne(targetEntity: Agent::class, inversedBy: 'fields')]
    public Agent $agent;

    /**
     * @see Reliability
     */
    #[ORM\Column(type: 'enum_reliability_type')]
    public string $reliability;

    /**
     * @see Source
     */
    #[ORM\Column(type: 'enum_reliability_type')]
    public string $source;

    #[ORM\Column(type: 'text')]
    public ?string $explanation;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getValue(): mixed
    {
        return $this->stringVal
            ?? $this->intVal
            ?? $this->floatVal
            ?? $this->datetimeVal;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
