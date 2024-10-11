<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * The most generic type of item.
 *
 * @see http://schema.org/Thing Documentation on Schema.org
 *
 * @ORM\Entity
 *
 * @ORM\Table(name="churches")
 *
 * @ApiResource(attributes={
 *   "normalization_context"={"groups"={"place","church"},"enable_max_depth"="true"}
 * })
 */
class Church
{
    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @ORM\Column(type="integer", name="church_id")
     *
     * @Groups("church")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="WikidataChurch", inversedBy="churches")
     *
     * @ORM\JoinColumn(nullable=true, referencedColumnName="wikidata_church_id")
     *
     * @Groups("church")
     *
     * @MaxDepth(1)
     */
    private ?WikidataChurch $wikidataChurch = null;

    /**
     * @ORM\ManyToOne(targetEntity="TheodiaChurch", inversedBy="churches")
     *
     * @ORM\JoinColumn(nullable=true, referencedColumnName="theodia_church_id")
     */
    private ?TheodiaChurch $theodiaChurch = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @ApiProperty(iri="http://schema.org/url")
     *
     * @Groups("church")
     */
    private ?string $massesUrl = null;

    /**
     * @ORM\OneToMany(targetEntity="Calendar", mappedBy="church")
     *
     * @Groups("church")
     *
     * @MaxDepth(1)
     **/
    private Collection $calendars;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCalendars(): Collection
    {
        return $this->calendars;
    }

    public function setTheodiaChurch(?TheodiaChurch $theodiaChurch): self
    {
        $this->theodiaChurch = $theodiaChurch;

        return $this;
    }

    public function setCalendars(Collection $calendars): self
    {
        $this->calendars = $calendars;

        return $this;
    }

    public function setWikidataChurch(?WikidataChurch $wikidataChurch): self
    {
        $this->wikidataChurch = $wikidataChurch;

        return $this;
    }

    public function getWikidataChurch(): ?WikidataChurch
    {
        return $this->wikidataChurch;
    }

    public function setTheodiaChurchId(TheodiaChurch $theodiaChurch): self
    {
        $this->theodiaChurch = $theodiaChurch;

        return $this;
    }

    public function getTheodiaChurch(): ?TheodiaChurch
    {
        return $this->theodiaChurch;
    }

    public function setMassesUrl(?string $massesUrl): self
    {
        $this->massesUrl = $massesUrl;

        return $this;
    }

    public function getMassesUrl(): ?string
    {
        return $this->massesUrl;
    }

    public function getDiocese(): ?Diocese
    {
        return $this->wikidataChurch ? $this->wikidataChurch->getDiocese() : null;
    }

    public function getParish(): ?Parish
    {
        return $this->wikidataChurch ? $this->wikidataChurch->getParish() : null;
    }
}
