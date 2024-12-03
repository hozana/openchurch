<?php

namespace App\Field\Domain\Model;

use App\Agent\Domain\Model\Agent;
use App\Community\Domain\Model\Community;
use App\Field\Domain\Enum\FieldCommunity;
use App\Field\Domain\Enum\FieldEngine;
use App\Field\Domain\Enum\FieldPlace;
use App\Field\Domain\Enum\FieldReliability;
use App\Place\Domain\Model\Place;
use App\Shared\Infrastructure\Doctrine\Trait\DoctrineTimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[ORM\Table]
#[ORM\UniqueConstraint(
    columns: ['community_id', 'place_id', 'name', 'agent_id'],
)]
class Field
{
    use DoctrineTimestampableTrait;
    public const UNIQUE_CONSTRAINTS = [
        FieldCommunity::MESSESINFO_ID->value,
        FieldCommunity::WIKIDATA_ID->value,
        FieldPlace::MESSESINFO_ID->value,
        FieldPlace::WIKIDATA_ID->value,
    ];

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    public ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Community::class, inversedBy: 'fields')]
    public ?Community $community = null;

    /**
     * Field name: not registered as enum
     *  - so that we can add more easily without a costly database migration ;
     *  - because communities and places doesn't share the same allowed values.
     *
     * @see CommunityFieldName
     * @see PlaceFieldName
     */
    #[ORM\Column]
    #[Groups(['communities', 'places'])]
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
    public ?\DateTimeImmutable $datetimeVal = null;

    #[ORM\Column(type: 'date', nullable: true)]
    public ?\DateTimeImmutable $dateVal = null;

    #[ORM\ManyToOne(targetEntity: Community::class, inversedBy: 'fieldsAsCommunityVal')]
    public ?Community $communityVal = null;

    /**
     * @var Collection<int, Community>
     */
    #[ORM\ManyToMany(targetEntity: Community::class, inversedBy: 'fieldsAsCommunitiesVal')]
    public Collection $communitiesVal;

    #[ORM\ManyToOne(targetEntity: Place::class, inversedBy: 'fieldsAsPlaceVal')]
    public ?Place $placeVal = null;

    /**
     * @var Collection<int, Place>
     */
    #[ORM\ManyToMany(targetEntity: Place::class, inversedBy: 'fieldsAsPlacesVal')]
    public Collection $placesVal;

    #[ORM\ManyToOne(targetEntity: Agent::class, inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['communities', 'places'])]
    public Agent $agent;

    /**
     * @see Reliability
     */
    #[Assert\Choice(callback: [FieldReliability::class, 'values'])]
    #[ORM\Column(type: 'enum_reliability_type')]
    #[Groups(['communities', 'places'])]
    public string $reliability;

    /**
     * @see FieldEngine
     */
    #[Assert\Choice(callback: [FieldEngine::class, 'values'])]
    #[ORM\Column(type: 'enum_engine_type')]
    #[Groups(['communities', 'places'])]
    public string $engine;

    /**
     * Where the data comes from (openstreetmap, for instance).
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['communities', 'places'])]
    public ?string $source;

    /**
     * Explanation of the source (its URL).
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['communities', 'places'])]
    public ?string $explanation;

    #[ORM\ManyToOne(inversedBy: 'fields')]
    public ?Place $place = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->communitiesVal = new ArrayCollection();
        $this->placesVal = new ArrayCollection();
    }

    #[Groups(['communities', 'places'])]
    #[SerializedName('value')]
    public function getValue(): mixed
    {
        /* @noinspection ProperNullCoalescingOperatorUsageInspection */
        return $this->stringVal
            ?? $this->intVal
            ?? $this->floatVal
            ?? $this->datetimeVal
            ?? $this->dateVal
            ?? $this->communityVal
            ?? $this->communitiesVal
            ?? $this->placeVal
            ?? $this->placesVal;
    }

    #[Assert\Callback()]
    public function validate(ExecutionContextInterface $context, mixed $payload): void
    {
        // Ensure either community or place is set, not none, not both
        if (!(null !== $this->community xor null !== $this->place)) {
            $context->buildViolation('Field must be attached to a community or a place, not none, not both')
                ->atPath('community')
                ->addViolation();
        }

        // Ensure name is OK according to community/place
        $enum = $this->getTypeEnum();
        if (null === $enum) {
            $context->buildViolation(sprintf('Field %s is not acceptable', $this->name))
                ->atPath('name')
                ->addViolation();
        }

        // Ensure type is OK
        if (null !== $this->value && null !== $enum && false !== $enum) {
            $type = $enum->getType();
            // Handle enums
            if (enum_exists($type)) {
                $type = array_column($type::cases(), 'value');
            }

            if (is_array($type)) {
                // That's an enum value! Validate its value
                if (!in_array($this->value, $type, true)) {
                    $context->buildViolation(sprintf('Field %s does not accept value %s (accepted values: %s)', $this->name, $this->value, implode(', ', $type)))
                        ->atPath('value')
                        ->addViolation();
                }
            } else {
                $isValid = match ($type) {
                    Types::STRING => is_string($this->value),
                    Types::FLOAT => is_float($this->value),
                    Types::INTEGER => is_int($this->value),
                    Types::DATETIME_MUTABLE => (bool) \DateTime::createFromFormat('Y-m-d H:i:s', $this->value),
                    Types::DATE_MUTABLE => (bool) \DateTime::createFromFormat('Y-m-d', $this->value),
                    'Community' => $this->value instanceof Community,
                    'Community[]' => is_array($this->value) && count($this->value) === count(array_filter($this->value, fn (mixed $item) => $item instanceof Community)),
                    'Place' => $this->value instanceof Place,
                    'Place[]' => is_array($this->value) && count($this->value) === count(array_filter($this->value, fn (mixed $item) => $item instanceof Place)),
                    default => false,
                };
                if (!$isValid) {
                    $context->buildViolation(sprintf('Field %s expected value of type %s', $this->name, $type))
                        ->atPath('value')
                        ->addViolation();
                }
            }
        }
    }

    private function getTypeEnum(): FieldCommunity|FieldPlace|false|null
    {
        return match (true) {
            null !== $this->community => FieldCommunity::tryFrom($this->name),
            null !== $this->place => FieldPlace::tryFrom($this->name),
            default => false, // Use false to avoid triggering "Field name is not acceptable" when neither community nor place is attached.
        };
    }

    public static function getPropertyName(FieldCommunity|FieldPlace $fieldName): string
    {
        $type = $fieldName->getType();
        // Special case: arrays
        if (enum_exists($type)) {
            $type = 'array';
        }

        return match ($type) {
            Types::STRING, 'array' => 'stringVal',
            Types::FLOAT => 'floatVal',
            Types::INTEGER => 'intVal',
            Types::DATETIME_MUTABLE => 'datetimeVal',
            Types::DATE_MUTABLE => 'dateVal',
            'Community' => 'communityVal',
            'Community[]' => 'communitiesVal',
            'Place' => 'placeVal',
            'Place[]' => 'placesVal',
            default => throw new \RuntimeException('Unknown type '.$type),
        };
    }

    public function applyValue(): void
    {
        $typeEnum = $this->getTypeEnum();
        if (false === $typeEnum) {
            throw new \RuntimeException('You must attach this Field to a Community or Place before attempting to call '.__METHOD__);
        }
        $propertyName = self::getPropertyName($typeEnum);

        $value = $this->value;
        if (is_array($this->value)) {
            $value = new ArrayCollection($value);
        }

        $propertyAccessor = new PropertyAccessor();
        $propertyAccessor->setValue($this, $propertyName, $value);
    }
}
