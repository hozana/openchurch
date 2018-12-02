<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The most generic type of item.
 *
 * @see http://schema.org/Thing Documentation on Schema.org
 *
 * @ORM\Entity
 * @ORM\Table(name="wikidata_churches")
 */
class WikidataChurch
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="wikidata_church_id")
     */
    private $id;

    /**
     * @var string|null the name of the item
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $name;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @Assert\NotNull
     */
    private $latitude;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @Assert\NotNull
     */
    private $longitude;

    /**
     * @var Place
     *
     * @ORM\ManyToOne(targetEntity="Place", inversedBy="wikidataChurches")
     * @ORM\JoinColumn(nullable=false, referencedColumnName="place_id")
     * @Assert\NotNull
     */
    private $place;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     * @Assert\NotNull
     */
    private $address;

    /**
     * @var array
     *
     * @ORM\OneToMany(targetEntity="Church", mappedBy="wikidataChurch")
     **/
    protected $churches;

    /**
     * @var array
     *
     * @ORM\OneToMany(targetEntity="Photo", mappedBy="wikidataChurch")
     **/
    protected $photos;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(type="datetime")
     * @Assert\DateTime
     * @Assert\NotNull
     */
    private $createdAt;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(type="datetime")
     * @Assert\DateTime
     * @Assert\NotNull
     */
    private $updatedAt;

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

    /**
     * @param float $latitude
     */
    public function setLatitude($latitude): void
    {
        $this->latitude = $latitude;
    }

    /**
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param float $longitude
     */
    public function setLongitude($longitude): void
    {
        $this->longitude = $longitude;
    }

    /**
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    public function setPlace(Place $place): void
    {
        $this->place = $place;
    }

    public function getPlace(): Place
    {
        return $this->place;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }
}
