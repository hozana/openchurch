<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\PlaceTypes;
use Doctrine\ORM\Mapping as ORM;
use Fresh\DoctrineEnumBundle\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The most generic type of item.
 *
 * @see http://schema.org/Thing Documentation on Schema.org
 *
 * @ORM\Entity
 * @ApiResource(iri="http://schema.org/Thing")
 */
class Places
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $placeId;

    /**
     * @var Places|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Places", inversedBy="children")
     * @ORM\JoinColumn(nullable=true, referencedColumnName="place_id")
     */
    private $parentId;

    /**
     * @var string|null the name of the item
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $name;

    /**
     * @ORM\Column(name="type", type="PlaceTypes", nullable=false)
     * @DoctrineAssert\Enum(entity="App\Enum\PlaceTypes")
     */
    private $type;

    /**
     * @var array
     *
     * @ORM\OneToMany(targetEntity="Places", mappedBy="parentId")
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

    public function getPlaceId(): ?int
    {
        return $this->placeId;
    }

    public function setParentId(?Places $parentId): void
    {
        $this->parentId = $parentId;
    }

    public function getParentId(): ?Places
    {
        return $this->parentId;
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
     * @param PlaceTypes $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return PlaceTypes
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
