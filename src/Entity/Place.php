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
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="place_id")
     * @Groups("place")
     */
    private $id;

    /**
     * @var Place|null
     *
     * @ORM\ManyToOne(targetEntity="Place", inversedBy="children")
     * @ORM\JoinColumn(nullable=true, referencedColumnName="place_id")
     */
    private $parent;

    /**
     * @var string|null the name of the item
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     * @Groups("place")
     */
    private $name;

    /**
     * @var string|null the country code of the item
     *
     * @ORM\Column(type="text", nullable=true)
     * @Groups("place")
     */
    private $countryCode;

    /**
     * @ORM\Column(name="type", type="PlaceType", nullable=false)
     * @DoctrineAssert\Enum(entity="App\Enum\PlaceType")
     * @Groups("place")
     */
    private $type;

    /**
     * @var array
     *
     * @ORM\OneToMany(targetEntity="WikidataChurch", mappedBy="place")
     **/
    private $wikidataChurches;

    /**
     * @var array
     *
     * @ORM\OneToMany(targetEntity="Place", mappedBy="parent")
     **/
    private $children;

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

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Diocese", mappedBy="country")
     */
    private $dioceses;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Parish", mappedBy="country")
     */
    private $parishes;

    public function __construct()
    {
        $this->dioceses = new ArrayCollection();
        $this->parishes = new ArrayCollection();
    }

    /**
     * @return Place[] $children
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param array $children
     */
    public function setChildren($children)
    {
        $this->children = $children;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setParent(?self $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setCountryCode(?string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    /**
     * @param PlaceType $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return PlaceType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param array $wikidataChurches
     */
    public function setWikidataChurches($wikidataChurches): void
    {
        $this->wikidataChurches = $wikidataChurches;
    }

    /**
     * @return array
     */
    public function getWikidataChurches()
    {
        return $this->wikidataChurches;
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
