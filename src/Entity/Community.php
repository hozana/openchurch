<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ApiResource]
#[ORM\Entity()]
#[ORM\Table()]
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


    #[Assert\Callback()]
    public function validate(ExecutionContextInterface $context, mixed $payload): void
    {
        // Ensure cross-field constraints
        // Groups:
        // - deletion reason must be set if state is deleted

        foreach ($this->getFieldsByName(CommunityFieldName::STATE) as $stateField) {
            if ($stateField->getValue() === 'deleted' && !$this->getFieldsByNameAndAgent(CommunityFieldName::DELETION_REASON, $stateField->agent)) {
                $context->buildViolation('Deletion reason is mandatory when reporting a state=deleted state.')
                    ->atPath('fields')
                    ->addViolation();
            }
        }
    }

    /**
     * @return Collection|Field[]
     */
    private function getFieldsByName(CommunityFieldName $name): Collection
    {
        return $this->fields
            ->filter(fn (Field $field) => $field->name === $name->value);
    }

    /**
     * @return Collection|Field[]
     */
    private function getFieldsByNameAndAgent(CommunityFieldName $name, Agent $agent): Collection
    {
        return $this->getFieldsByName($name)
            ->filter(fn (Field $field) => $field->agent === $agent);
    }
}
