<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The most generic type of item.
 *
 * @see http://schema.org/Thing Documentation on Schema.org
 *
 * @ORM\Entity
 * @ORM\Table(name="wikidata_churches")
 * @ApiResource(attributes={
 *   "normalization_context"={"groups"={"place","church"},"enable_max_depth"="true"}
 * })
 */
class WikidataChurch
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="wikidata_church_id")
     * @Groups("church")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups("church")
     */
    private ?string $name = null;

    /**
     * @ORM\Column(type="float")
     * @Assert\NotNull
     * @Groups("church")
     */
    private ?float $latitude = null;

    /**
     * @ORM\Column(type="float")
     * @Assert\NotNull
     * @Groups("church")
     */
    private ?float $longitude = null;

    /**
     * @ORM\ManyToOne(targetEntity="Place", inversedBy="wikidataChurches")
     * @ORM\JoinColumn(nullable=false, referencedColumnName="place_id")
     * @Assert\NotNull
     * @Groups("place")
     * @MaxDepth(1)
     */
    private ?Place $place = null;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotNull
     * @Groups("church")
     */
    private string $address = '';

    /**
     * @ORM\OneToMany(targetEntity="Church", mappedBy="wikidataChurch")
     * @Groups("church")
     * @MaxDepth(1)
     **/
    private Collection $churches;

    /**
     * @ORM\OneToMany(targetEntity="Photo", mappedBy="wikidataChurch")
     * @Groups("church")
     * @MaxDepth(1)
     **/
    private Collection $photos;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime
     * @Assert\NotNull
     */
    private ?\DateTimeInterface $createdAt = null;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime
     * @Assert\NotNull
     * @Groups("church")
     */
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @ORM\ManyToOne(targetEntity=Parish::class, inversedBy="wikidataChurches")
     * @ORM\JoinColumn(nullable=true, referencedColumnName="parish_id")
     * @Groups("parish")
     * @MaxDepth(1)
     */
    private ?Parish $parish = null;

    /**
     * @ORM\ManyToOne(targetEntity=Diocese::class, inversedBy="wikidataChurches")
     * @ORM\JoinColumn(nullable=true, referencedColumnName="diocese_id")
     * @Groups("diocese")
     * @MaxDepth(1)
     */
    private ?Diocese $diocese = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $messesinfoId = '';

    public function getChurches(): Collection
    {
        return $this->churches;
    }

    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function setChurches(Collection $churches): self
    {
        $this->churches = $churches;

        return $this;
    }

    public function setPhotos(Collection $photos): self
    {
        $this->photos = $photos;

        return $this;
    }

    /**
     * @Groups("church")
     */
    public function getPin(): string
    {
        return $this->latitude.','.$this->longitude;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setPlace(Place $place): self
    {
        $this->place = $place;

        return $this;
    }

    public function getPlace(): ?Place
    {
        return $this->place;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getParish(): ?Parish
    {
        return $this->parish;
    }

    public function setParish(?Parish $parish): self
    {
        $this->parish = $parish;

        return $this;
    }

    public function getDiocese(): ?Diocese
    {
        return $this->diocese;
    }

    public function setDiocese(?Diocese $diocese): self
    {
        $this->diocese = $diocese;

        return $this;
    }

    public function getMessesinfoId(): string
    {
        return $this->messesinfoId;
    }

    public function setMessesinfoId(string $messesinfoId): self
    {
        $this->messesinfoId = $messesinfoId;

        return $this;
    }
}
