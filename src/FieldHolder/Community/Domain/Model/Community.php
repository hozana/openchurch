<?php

namespace App\FieldHolder\Community\Domain\Model;

use Stringable;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Model\Field;
use App\FieldHolder\FieldHolder;
use App\Shared\Infrastructure\Doctrine\Trait\DoctrineTimestampableTrait;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[ORM\Table]
class Community extends FieldHolder implements Stringable
{
    use DoctrineTimestampableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null;

    /**
     * @var Collection<int, Field>
     */
    #[ORM\OneToMany(targetEntity: Field::class, mappedBy: 'community')]
    #[Groups(['communities'])]
    public Collection $fields;

    /**
     * @var Collection<int, Field>
     */
    #[ORM\OneToMany(targetEntity: Field::class, mappedBy: 'communityVal')]
    public Collection $fieldsAsCommunityVal;

    /**
     * @var Collection<int, Field>
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
        return $this->id->toString();
    }

    #[Assert\Callback()]
    public function validate(ExecutionContextInterface $context, mixed $payload): void
    {
        // Ensure cross-field constraints
        // Groups:
        // - deletion reason must be set if state is deleted
        foreach ($this->getFieldsByName(FieldCommunity::STATE) as $stateField) {
            if ('deleted' === $stateField->getValue() && !$this->getFieldByNameAndAgent(FieldCommunity::DELETION_REASON, $stateField->agent)) {
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
        // set the owning side to null (unless already changed)
        if ($this->fields->removeElement($field) && $field->community === $this) {
            $field->community = null;
        }

        return $this;
    }
}
