<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("parish")
     */
    private string $name = '';

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Diocese", inversedBy="parishes")
     * @ORM\JoinColumn(nullable=true, referencedColumnName="diocese_id")
     * @Groups("diocese")
     */
    private ?Diocese $diocese = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Place", inversedBy="parishes")
     * @ORM\JoinColumn(nullable=true, referencedColumnName="place_id")
     * @Groups("place")
     */
    private ?Place $country = null;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("parish")
     */
    private string $messesinfoId = '';

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("parish")
     */
    private string $website = '';

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("parish")
     */
    private string $zipCode = '';

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
     */
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @ORM\OneToMany(targetEntity=WikidataChurch::class, mappedBy="parish")
     */
    private Collection $wikidataChurches;

    public function __construct()
    {
        $this->wikidataChurches = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, WikidataChurch>
     */
    public function getWikidataChurches(): Collection
    {
        return $this->wikidataChurches;
    }

    public function addWikidataChurch(WikidataChurch $wikidataChurch): self
    {
        if (!$this->wikidataChurches->contains($wikidataChurch)) {
            $this->wikidataChurches[] = $wikidataChurch;
            $wikidataChurch->setParish($this);
        }

        return $this;
    }

    public function removeWikidataChurch(WikidataChurch $wikidataChurch): self
    {
        if ($this->wikidataChurches->removeElement($wikidataChurch)) {
            // set the owning side to null (unless already changed)
            if ($wikidataChurch->getParish() === $this) {
                $wikidataChurch->setParish(null);
            }
        }

        return $this;
    }
}
