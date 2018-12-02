<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\PlaceType;
use Doctrine\ORM\Mapping as ORM;
use Fresh\DoctrineEnumBundle\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The most generic type of item.
 *
 * @see http://schema.org/Thing Documentation on Schema.org
 *
 * @ORM\Entity
 * @ORM\Table(name="places")
 * @ApiResource(iri="http://schema.org/Thing")
 */
class Place
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="place_id")
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
     */
    private $name;

    /**
     * @var string|null the country code of the item
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $countryCode;

    /**
     * @ORM\Column(name="type", type="PlaceType", nullable=false)
     * @DoctrineAssert\Enum(entity="App\Enum\PlaceType")
     */
    private $type;

    /**
     * @var array
     *
     * @ORM\OneToMany(targetEntity="WikidataChurch", mappedBy="place")
     **/
    protected $wikidataChurches;

    /**
     * @var array
     *
     * @ORM\OneToMany(targetEntity="Place", mappedBy="parent")
     **/
    protected $children;

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

    public function setParent(?Place $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?Place
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
