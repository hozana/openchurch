<?php

namespace App\FieldHolder\Place\Domain\Model;

use App\Agent\Domain\Model\Agent;
use App\Field\Domain\Enum\FieldPlace;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Domain\Model\FieldHolder;
use App\Shared\Infrastructure\Doctrine\Trait\DoctrineTimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[ORM\Table]
class Place extends FieldHolder
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
    #[ORM\OneToMany(targetEntity: Field::class, mappedBy: 'placeVal')]
    public Collection $fieldsAsPlaceVal;

    /**
     * @var Collection<int, Field>
     */
    #[ORM\ManyToMany(targetEntity: Field::class, mappedBy: 'placesVal')]
    public Collection $fieldsAsPlacesVal;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->fieldsAsPlaceVal = new ArrayCollection();
        $this->fieldsAsPlacesVal = new ArrayCollection();
        $this->fields = new ArrayCollection();
    }

    #[Assert\Callback()]
    public function validate(ExecutionContextInterface $context, mixed $payload): void
    {
        // Ensure cross-field constraints
        // Groups:
        // - deletion reason must be set if state is deleted
        foreach ($this->getFieldsByName(FieldPlace::STATE) as $stateField) {
            if ('deleted' === $stateField->getValue() && !$this->getFieldByNameAndAgent(FieldPlace::DELETION_REASON, $stateField->agent)) {
                $context->buildViolation('Deletion reason is mandatory when reporting a state=deleted state.')
                    ->atPath('fields')
                    ->addViolation();
            }
        }

        // Country code validation
        foreach ($this->getFieldsByName(FieldPlace::COUNTRY_CODE) as $countryCodeField) {
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
            $field->place = $this;
        }

        return $this;
    }

    public function removeField(Field $field): static
    {
        if ($this->fields->removeElement($field)) {
            // set the owning side to null (unless already changed)
            if ($field->place === $this) {
                $field->place = null;
            }
        }

        return $this;
    }
}
