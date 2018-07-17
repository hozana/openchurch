<?php

declare(strict_types=1);

namespace Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A church.
 *
 * @see http://schema.org/Church Documentation on Schema.org
 *
 * @ORM\Entity
 * @ApiResource(iri="http://schema.org/Church")
 */
class Church
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string|null the name of the item
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $name;

    /**
     * @var string|null an alias for the item
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/alternateName")
     */
    private $alternateName;

    /**
     * @var string|null a description of the item
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    private $description;

    /**
     * @var PostalAddress|null physical address of the item
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\PostalAddress")
     * @ApiProperty(iri="http://schema.org/address")
     */
    private $address;

    /**
     * @var string|null a URL to a map of the place
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/hasMap")
     * @Assert\Url
     */
    private $hasMap;

    /**
     * @var string|null the telephone number
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/telephone")
     */
    private $telephone;

    /**
     * @var string|null the fax number
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/faxNumber")
     */
    private $faxNumber;

    /**
     * @var text|null URL of the item
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\")
     * @ApiProperty(iri="http://schema.org/url")
     */
    private $url;

    /**
     * @var GeoCoordinates|null the geo coordinates of the place
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\GeoCoordinates")
     * @ApiProperty(iri="http://schema.org/geo")
     */
    private $geo;

    /**
     * @var text|null a photograph of this place
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\")
     * @ApiProperty(iri="http://schema.org/photo")
     */
    private $photo;

    /**
     * @var text|null an associated logo
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\")
     * @ApiProperty(iri="http://schema.org/logo")
     */
    private $logo;

    /**
     * @var bool|null a flag to signal that the item, event, or place is accessible for free
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty(iri="http://schema.org/isAccessibleForFree")
     */
    private $isAccessibleForFree;

    /**
     * @var bool|null A flag to signal that the \[\[Place\]\] is open to public visitors. If this property is omitted there is no assumed default boolean value
     *
     * @ORM\Column(type="boolean", nullable=true)
     * @ApiProperty(iri="http://schema.org/publicAccess")
     */
    private $publicAccess;

    /**
     * @var int|null the total number of individuals that may attend an event or venue
     *
     * @ORM\Column(type="integer", nullable=true)
     * @ApiProperty(iri="http://schema.org/maximumAttendeeCapacity")
     */
    private $maximumAttendeeCapacity;

    /**
     * @var string|null An additional type for the item, typically used for adding more specific types from external vocabularies in microdata syntax. This is a relationship between something and a class that the thing is in. In RDFa syntax, it is better to use the native RDFa syntax - the 'typeof' attribute - for multiple types. Schema.org tools may have only weaker understanding of extra types, in particular those defined externally.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/additionalType")
     * @Assert\Url
     */
    private $additionalType;

    /**
     * @var Event|null upcoming or past event associated with this place, organization, or action
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Event")
     * @ApiProperty(iri="http://schema.org/event")
     */
    private $event;

    /**
     * @var Review|null a review of the item
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Review")
     * @ApiProperty(iri="http://schema.org/review")
     */
    private $review;

    /**
     * @var string|null The general opening hours for a business. Opening hours can be specified as a weekly time range, starting with days, then times per day. Multiple days can be listed with commas ',' separating each day. Day or time ranges are specified using a hyphen '-'.\\n\\n\* Days are specified using the following two-letter combinations: ```Mo```, ```Tu```, ```We```, ```Th```, ```Fr```, ```Sa```, ```Su```.\\n\* Times are specified using 24:00 time. For example, 3pm is specified as ```15:00```. \\n\* Here is an example: `<time itemprop="openingHours" datetime="Tu,Th 16:00-20:00">Tuesdays and Thursdays 4-8pm</time>`.\\n\* If a business is open 7 days a week, then it can be specified as `<time itemprop="openingHours" datetime="Mo-Su">Monday through Sunday, all day</time>`.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/openingHours")
     */
    private $openingHour;

    /**
     * @var Collection<OpeningHoursSpecification>|null the opening hours of a certain place
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\OpeningHoursSpecification")
     * @ORM\JoinTable(inverseJoinColumns={@ORM\JoinColumn(unique=true)})
     * @ApiProperty(iri="http://schema.org/openingHoursSpecification")
     */
    private $openingHoursSpecifications;

    /**
     * @var OpeningHoursSpecification|null The special opening hours of a certain place.\\n\\nUse this to explicitly override general opening hours brought in scope by \[\[openingHoursSpecification\]\] or \[\[openingHours\]\].
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\OpeningHoursSpecification")
     * @ApiProperty(iri="http://schema.org/specialOpeningHoursSpecification")
     */
    private $specialOpeningHoursSpecification;

    public function __construct()
    {
        $this->openingHoursSpecifications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setAlternateName(?string $alternateName): void
    {
        $this->alternateName = $alternateName;
    }

    public function getAlternateName(): ?string
    {
        return $this->alternateName;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setAddress(?PostalAddress $address): void
    {
        $this->address = $address;
    }

    public function getAddress(): ?PostalAddress
    {
        return $this->address;
    }

    public function setHasMap(?string $hasMap): void
    {
        $this->hasMap = $hasMap;
    }

    public function getHasMap(): ?string
    {
        return $this->hasMap;
    }

    public function setTelephone(?string $telephone): void
    {
        $this->telephone = $telephone;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setFaxNumber(?string $faxNumber): void
    {
        $this->faxNumber = $faxNumber;
    }

    public function getFaxNumber(): ?string
    {
        return $this->faxNumber;
    }

    /**
     * @param text|null $url
     */
    public function setUrl($url): void
    {
        $this->url = $url;
    }

    /**
     * @return text|null
     */
    public function getUrl()
    {
        return $this->url;
    }

    public function setGeo(?GeoCoordinates $geo): void
    {
        $this->geo = $geo;
    }

    public function getGeo(): ?GeoCoordinates
    {
        return $this->geo;
    }

    /**
     * @param text|null $photo
     */
    public function setPhoto($photo): void
    {
        $this->photo = $photo;
    }

    /**
     * @return text|null
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * @param text|null $logo
     */
    public function setLogo($logo): void
    {
        $this->logo = $logo;
    }

    /**
     * @return text|null
     */
    public function getLogo()
    {
        return $this->logo;
    }

    public function setIsAccessibleForFree(?bool $isAccessibleForFree): void
    {
        $this->isAccessibleForFree = $isAccessibleForFree;
    }

    public function getIsAccessibleForFree(): ?bool
    {
        return $this->isAccessibleForFree;
    }

    public function setPublicAccess(?bool $publicAccess): void
    {
        $this->publicAccess = $publicAccess;
    }

    public function getPublicAccess(): ?bool
    {
        return $this->publicAccess;
    }

    public function setMaximumAttendeeCapacity(?int $maximumAttendeeCapacity): void
    {
        $this->maximumAttendeeCapacity = $maximumAttendeeCapacity;
    }

    public function getMaximumAttendeeCapacity(): ?int
    {
        return $this->maximumAttendeeCapacity;
    }

    public function setAdditionalType(?string $additionalType): void
    {
        $this->additionalType = $additionalType;
    }

    public function getAdditionalType(): ?string
    {
        return $this->additionalType;
    }

    public function setEvent(?Event $event): void
    {
        $this->event = $event;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setReview(?Review $review): void
    {
        $this->review = $review;
    }

    public function getReview(): ?Review
    {
        return $this->review;
    }

    public function setOpeningHour(?string $openingHour): void
    {
        $this->openingHour = $openingHour;
    }

    public function getOpeningHour(): ?string
    {
        return $this->openingHour;
    }

    public function addOpeningHoursSpecification(OpeningHoursSpecification $openingHoursSpecification): void
    {
        $this->openingHoursSpecifications[] = $openingHoursSpecification;
    }

    public function removeOpeningHoursSpecification(OpeningHoursSpecification $openingHoursSpecification): void
    {
        $this->openingHoursSpecifications->removeElement($openingHoursSpecification);
    }

    public function getOpeningHoursSpecifications(): Collection
    {
        return $this->openingHoursSpecifications;
    }

    public function setSpecialOpeningHoursSpecification(?OpeningHoursSpecification $specialOpeningHoursSpecification): void
    {
        $this->specialOpeningHoursSpecification = $specialOpeningHoursSpecification;
    }

    public function getSpecialOpeningHoursSpecification(): ?OpeningHoursSpecification
    {
        return $this->specialOpeningHoursSpecification;
    }
}
