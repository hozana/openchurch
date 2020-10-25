<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource()
 * @ORM\Table(name="parishes")
 * @ORM\Entity(repositoryClass="App\Repository\ParishRepository")
 */
class Parish
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", name="parish_id")
     * @Groups("parish")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("parish")
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Diocese", inversedBy="parishes")
     * @ORM\JoinColumn(nullable=true, referencedColumnName="diocese_id")
     * @Groups("diocese")
     */
    private $diocese;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Place", inversedBy="parishes")
     * @ORM\JoinColumn(nullable=true, referencedColumnName="place_id")
     * @Groups("place")
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("parish")
     */
    private $messesinfoId;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("parish")
     */
    private $website;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("parish")
     */
    private $zipCode;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

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

    public function getCountry(): ?Place
    {
        return $this->country;
    }

    public function setCountry(?Place $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getMessesinfoId(): ?string
    {
        return $this->messesinfoId;
    }

    public function setMessesinfoId(string $messesinfoId): self
    {
        $this->messesinfoId = $messesinfoId;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(string $zipCode): self
    {
        $this->zipCode = $zipCode;

        return $this;
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
