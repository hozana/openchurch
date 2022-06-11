<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\PlaceType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Fresh\DoctrineEnumBundle\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The most generic type of item.
 *
 * @see http://schema.org/Thing Documentation on Schema.org
 *
 * @ORM\Entity
 * @ORM\Table(name="places")
 * @ApiResource(attributes={
 *   "normalization_context"={"groups"={"place","church"},"enable_max_depth"="true"}
 * })
 */
class Place
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="place_id")
     * @Groups("place")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Place", inversedBy="children")
     * @ORM\JoinColumn(nullable=true, referencedColumnName="place_id")
     */
    private ?Place $parent = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups("place")
     */
    private ?string $name = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups("place")
     */
    private ?string $countryCode = null;

    /**
     * @ORM\Column(name="type", type="PlaceType", nullable=false)
     * @DoctrineAssert\Enum(entity="App\Enum\PlaceType")
     * @Groups("place")
     */
    private ?string $type = null;

    /**
     * @ORM\OneToMany(targetEntity="WikidataChurch", mappedBy="place")
     **/
    private Collection $wikidataChurches;

    /**
     * @ORM\OneToMany(targetEntity="Place", mappedBy="parent")
     **/
    private Collection $children;

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
     * @ORM\OneToMany(targetEntity="App\Entity\Diocese", mappedBy="country")
     */
    private Collection $dioceses;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Parish", mappedBy="country")
     */
    private Collection $parishes;

    public function __construct()
    {
        $this->dioceses = new ArrayCollection();
        $this->parishes = new ArrayCollection();
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function setChildren(Collection $children): self
    {
        $this->children = $children;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
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

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setWikidataChurches(Collection $wikidataChurches): self
    {
        $this->wikidataChurches = $wikidataChurches;

        return $this;
    }

    public function getWikidataChurches(): Collection
    {
        return $this->wikidataChurches;
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

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection|Diocese[]
     */
    public function getDioceses(): Collection
    {
        return $this->dioceses;
    }

    public function addDiocese(Diocese $diocese): self
    {
        if (!$this->dioceses->contains($diocese)) {
            $this->dioceses[] = $diocese;
            $diocese->setCountry($this);
        }

        return $this;
    }

    public function removeDiocese(Diocese $diocese): self
    {
        if ($this->dioceses->contains($diocese)) {
            $this->dioceses->removeElement($diocese);
            // set the owning side to null (unless already changed)
            if ($diocese->getCountry() === $this) {
                $diocese->setCountry(null);
            }
        }

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
            $parish->setCountry($this);
        }

        return $this;
    }

    public function removeParish(Parish $parish): self
    {
        if ($this->parishes->contains($parish)) {
            $this->parishes->removeElement($parish);
            // set the owning side to null (unless already changed)
            if ($parish->getCountry() === $this) {
                $parish->setCountry(null);
            }
        }

        return $this;
    }
}
