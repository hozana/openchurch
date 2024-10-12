<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Helper\Trait\Timestampable;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table]
class Community
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
    #[ORM\OneToMany(targetEntity: Field::class, mappedBy: 'community')]
    public Collection $fields;

    /**
     * @var ArrayCollection|Field[]
     */
    #[ORM\OneToMany(targetEntity: Field::class, mappedBy: 'communityVal')]
    public Collection $fieldsAsCommunityVal;

    /**
     * @var ArrayCollection|Field[]
     */
    #[ORM\ManyToMany(targetEntity: Field::class, mappedBy: 'communitiesVal')]
    public Collection $fieldsAsCommunitiesVal;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->fields = new ArrayCollection();
        $this->fieldsAsCommunityVal = new ArrayCollection();
        $this->fieldsAsCommunitiesVal = new ArrayCollection();
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
            if ($stateField->getValue() === 'deleted' && !$this->getFieldByNameAndAgent(CommunityFieldName::DELETION_REASON, $stateField->agent)) {
                $context->buildViolation('Deletion reason is mandatory when reporting a state=deleted state.')
                    ->atPath('fields')
                    ->addViolation();
            }
        }
    }

    /**
     * @return Collection|Field[]
     */
    public function getFieldsByName(CommunityFieldName $name): Collection
    {
        return $this->fields
            ->filter(fn (Field $field) => $field->name === $name->value);
    }

    public function getFieldByNameAndAgent(CommunityFieldName $name, Agent $agent): ?Field
    {
        return $this->getFieldsByName($name)
            ->filter(fn (Field $field) => $field->agent === $agent)
            ->first() ?: null;
    }
}
