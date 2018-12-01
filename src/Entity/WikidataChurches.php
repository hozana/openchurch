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
 */
class WikidataChurches
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $wikidataChurchId;

    /**
     * @var string|null the name of the item
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $name;

    /**
     * @var Photos
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Photos", inversedBy="wikidataChurches")
     * @ORM\JoinColumn(nullable=false, referencedColumnName="photo_id")
     * @Assert\NotNull
     */
    private $photoId;

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
     * @var Places
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Places", inversedBy="wikidataChurches")
     * @ORM\JoinColumn(nullable=false, referencedColumnName="place_id")
     * @Assert\NotNull
     */
    private $placeId;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     * @Assert\NotNull
     */
    private $address;

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

    public function getWikidataChurchId(): ?int
    {
        return $this->wikidataChurchId;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setPhotoId(Photos $photoId): void
    {
        $this->photoId = $photoId;
    }

    public function getPhotoId(): Photos
    {
        return $this->photoId;
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

    public function setPlaceId(Places $placeId): void
    {
        $this->placeId = $placeId;
    }

    public function getPlaceId(): Places
    {
        return $this->placeId;
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
