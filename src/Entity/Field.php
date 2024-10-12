<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Helper\Trait\Timestampable;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ApiResource]
#[ORM\Entity()]
#[ORM\Table()]
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

    /**
     * Temporary place to store the value until it's validated.
     */
    public mixed $value = null;

    #[ORM\Column(nullable: true)]
    public ?string $stringVal = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    public ?int $intVal = null;

    #[ORM\Column(type: 'float', nullable: true)]
    public ?float $floatVal = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    public ?DateTimeImmutable $datetimeVal = null;

    #[ORM\Column(type: 'date', nullable: true)]
    public ?DateTimeImmutable $dateVal = null;

    #[ORM\ManyToOne(targetEntity: Agent::class, inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false)]
    public Agent $agent;

    /**
     * @see Reliability
     */
    #[Assert\Choice(callback: [Reliability::class, 'values'])]
    #[ORM\Column(type: 'enum_reliability_type')]
    public string $reliability;

    /**
     * @see Source
     */
    #[Assert\Choice(callback: [Source::class, 'values'])]
    #[ORM\Column(type: 'enum_source_type')]
    public string $source;

    #[ORM\Column(type: 'text')]
    public ?string $explanation;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getValue(): mixed
    {
        /** @noinspection ProperNullCoalescingOperatorUsageInspection */
        return $this->stringVal
            ?? $this->intVal
            ?? $this->floatVal
            ?? $this->datetimeVal
            ?? $this->dateVal;
    }

    public function __toString(): string
    {
        return $this->id;
    }

    #[Assert\Callback()]
    public function validate(ExecutionContextInterface $context, mixed $payload): void
    {
        // Ensure either community or place is set, not both
        if ($this->community === null && $this->place === null) {
            $context->buildViolation('Field must be attached to community or place')
                ->atPath('community')
                ->addViolation();
        }
        if ($this->community !== null && $this->place !== null) {
            $context->buildViolation('Field must be attached to community or place, not both!')
                ->atPath('community')
                ->addViolation();
        }

        // Ensure name is OK according to community/place
        $enum = match (true) {
            $this->community !== null => CommunityFieldName::tryFrom($this->name),
            $this->place !== null => PlaceFieldName::tryFrom($this->name),
            default => null,
        };
        if ($enum === null) {
            $context->buildViolation(sprintf('Field %s is not acceptable', $this->name))
                ->atPath('name')
                ->addViolation();
        }

        // Ensure type is OK
        if ($this->value !== null && $enum !== null) {
            $type = $enum->getType();

            if (is_array($type)) {
                // That's an enum value! Validate its value
                if (!in_array($this->value, $type, true)) {
                    $context->buildViolation(sprintf('Field %s does not accept value %s (accepted values: %s)', $this->name, $this->value, implode(',', $type)))
                        ->atPath('value')
                        ->addViolation();
                }
            } else {
                $isValid = match ($type) {
                    Types::STRING => is_string($this->value),
                    Types::FLOAT => is_float($this->value),
                    Types::INTEGER => is_int($this->value),
                    Types::DATETIME_MUTABLE => DateTime::createFromFormat('Y-m-d H:i:s', $this->value) !== null,
                    Types::DATE_MUTABLE => DateTime::createFromFormat('Y-m-d', $this->value) !== null,
                };
                if (!$isValid) {
                    $context->buildViolation(sprintf('Field %s expected value of type %s', $this->name, $type))
                        ->atPath('value')
                        ->addViolation();
                }
            }
        }
    }
}
