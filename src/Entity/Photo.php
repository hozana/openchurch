<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The most generic type of item.
 *
 * @see http://schema.org/Thing Documentation on Schema.org
 *
 * @ORM\Entity
 *
 * @ORM\Table(name="photos")
 *
 * @ApiResource(iri="http://schema.org/Thing")
 */
class Photo
{
    /**
     * @var int|null
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @ORM\Column(type="integer", name="photo_id")
     */
    private $id;

    /**
     * @var WikidataChurch
     *
     * @ORM\ManyToOne(targetEntity="WikidataChurch", inversedBy="photos")
     *
     * @ORM\JoinColumn(nullable=true, referencedColumnName="wikidata_church_id")
     */
    private $wikidataChurch;

    /**
     * @var TheodiaChurch
     *
     * @ORM\ManyToOne(targetEntity="TheodiaChurch", inversedBy="photos")
     *
     * @ORM\JoinColumn(nullable=true, referencedColumnName="theodia_church_id")
     */
    private $theodiaChurch;

    /**
     * @var string|null URL of the item
     *
     * @ORM\Column(type="text", nullable=true)
     *
     * @ApiProperty(iri="http://schema.org/url")
     */
    private $url;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(type="datetime")
     *
     * @Assert\DateTime
     *
     * @Assert\NotNull
     */
    private $createdAt;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(type="datetime")
     *
     * @Assert\DateTime
     *
     * @Assert\NotNull
     */
    private $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setWikidataChurch(WikidataChurch $wikidataChurch): self
    {
        $this->wikidataChurch = $wikidataChurch;

        return $this;
    }

    public function getWikidataChurch(): WikidataChurch
    {
        return $this->wikidataChurch;
    }

    public function setTheodiaChurch(TheodiaChurch $theodiaChurch): self
    {
        $this->theodiaChurch = $theodiaChurch;

        return $this;
    }

    public function getTheodiaChurch(): TheodiaChurch
    {
        return $this->theodiaChurch;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }
}
