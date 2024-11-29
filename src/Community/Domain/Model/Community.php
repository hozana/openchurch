<?php

namespace App\Community\Domain\Model;

use App\Agent\Domain\Model\Agent;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldReliability;
use App\Field\Domain\Model\Field;
use App\Shared\Infrastructure\Doctrine\Trait\DoctrineTimestampableTrait;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[ORM\Table]
class Community
{
    use DoctrineTimestampableTrait;

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

    #[Assert\Callback()]
    public function validate(ExecutionContextInterface $context, mixed $payload): void
    {
        // Ensure cross-field constraints
        // Groups:
        // - deletion reason must be set if state is deleted
        foreach ($this->getFieldsByName(FieldCommunity::STATE) as $stateField) {
            if ($stateField->getValue() === 'deleted' && !$this->getFieldByNameAndAgent(FieldCommunity::DELETION_REASON, $stateField->agent)) {
                $context->buildViolation('Deletion reason is mandatory when reporting a state=deleted state.')
                    ->atPath('fields')
                    ->addViolation();
            }
        }

        // Country code validation
        foreach ($this->getFieldsByName(FieldCommunity::CONTACT_COUNTRY_CODE) as $countryCodeField) {
            if ((null !== $countryCode = $countryCodeField->getValue()) && !Countries::exists($countryCode)) {
                $context->buildViolation("Country code '$countryCode' is not valid.")
                    ->atPath('fields')
                    ->addViolation();
            }
        }
    }

    /**
     * @return Collection|Field[]
     */
    public function getFieldsByName(FieldCommunity $name): Collection
    {
        return $this->fields
            ->filter(fn (Field $field) => $field->name === $name->value);
    }

    public function getFieldByNameAndAgent(FieldCommunity $name, Agent $agent): ?Field
    {
        return $this->getFieldsByName($name)
            ->filter(fn (Field $field) => $field->agent === $agent)
            ->first() ?: null;
    }

    public function getMostTrustableFieldByName(FieldCommunity $name): ?Field
    {      
        $result = $this->getFieldsByName($name)->toArray();
        if (count($result) === 0) {
            return null;
        }

        usort($result, function (Field $a, Field $b) {
            return FieldReliability::compare($a->reliability, $b->reliability);
        });

        return $result[0];
    }

    public function addField(Field $field): static
    {
        if (!$this->fields->contains($field)) {
            $this->fields->add($field);
            $field->community = $this;
        }

        return $this;
    }

    public function removeField(Field $field): static
    {
        if ($this->fields->removeElement($field)) {
            // set the owning side to null (unless already changed)
            if ($field->community === $this) {
                $field->community = null;
            }
        }

        return $this;
    }
}
