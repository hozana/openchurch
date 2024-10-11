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
 *
 * @ORM\Table(name="dioceses")
 *
 * @ORM\Entity(repositoryClass="App\Repository\DioceseRepository")
 */
class Diocese
{
    /**
     * @ORM\Id()
     *
     * @ORM\GeneratedValue()
     *
     * @ORM\Column(type="integer", name="diocese_id")
     *
     * @Groups("diocese")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Groups("diocese")
     */
    private string $name = '';

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Place", inversedBy="dioceses")
     *
     * @ORM\JoinColumn(nullable=true, referencedColumnName="place_id")
     *
     * @Groups("place")
     */
    private ?Place $country = null;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Groups("diocese")
     */
    private string $website = '';

    /**
     * @ORM\Column(type="datetime")
     *
     * @Assert\DateTime
     *
     * @Assert\NotNull
     */
    private ?\DateTimeInterface $createdAt = null;

    /**
     * @ORM\Column(type="datetime")
     *
     * @Assert\DateTime
     *
     * @Assert\NotNull
     */
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Parish", mappedBy="diocese")
     */
    private Collection $parishes;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $gcatholicId = '';

    /**
     * @ORM\OneToMany(targetEntity=WikidataChurch::class, mappedBy="diocese")
     */
    private Collection $wikidataChurches;

    public function __construct()
    {
        $this->parishes = new ArrayCollection();
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

    public function getCountry(): ?Place
    {
        return $this->country;
    }

    public function setCountry(?Place $country): self
    {
        $this->country = $country;

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

    /**
     * @return Collection|Parish[]
     */
    public function getParishes(): Collection
    {
        return $this->parishes;
    }

    public function addParish(Parish $parish): self
    {
        if (!$this->parishes->contains($parish)) {
            $this->parishes[] = $parish;
            $parish->setDiocese($this);
        }

        return $this;
    }

    public function removeParish(Parish $parish): self
    {
        if ($this->parishes->contains($parish)) {
            $this->parishes->removeElement($parish);
            // set the owning side to null (unless already changed)
            if ($parish->getDiocese() === $this) {
                $parish->setDiocese(null);
            }
        }

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

    public function getGcatholicId(): string
    {
        return $this->gcatholicId;
    }

    public function setGcatholicId(string $gcatholicId): self
    {
        $this->gcatholicId = $gcatholicId;

        return $this;
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
            $wikidataChurch->setDiocese($this);
        }

        return $this;
    }

    public function removeWikidataChurch(WikidataChurch $wikidataChurch): self
    {
        if ($this->wikidataChurches->removeElement($wikidataChurch)) {
            // set the owning side to null (unless already changed)
            if ($wikidataChurch->getDiocese() === $this) {
                $wikidataChurch->setDiocese(null);
            }
        }

        return $this;
    }
}
