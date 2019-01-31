<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * The most generic type of item.
 *
 * @see http://schema.org/Thing Documentation on Schema.org
 *
 * @ORM\Entity
 * @ORM\Table(name="churches")
 * @ApiResource(attributes={
 *   "normalization_context"={"groups"={"place","church"},"enable_max_depth"="true"}
 * })
 */
class Church
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="church_id")
     * @Groups("church")
     */
    private $id;

    /**
     * @var WikidataChurch
     *
     * @ORM\ManyToOne(targetEntity="WikidataChurch", inversedBy="churches")
     * @ORM\JoinColumn(nullable=true, referencedColumnName="wikidata_church_id")
     * @Groups("church")
     * @MaxDepth(1)
     */
    private $wikidataChurch;

    /**
     * @var TheodiaChurch
     *
     * @ORM\ManyToOne(targetEntity="TheodiaChurch", inversedBy="churches")
     * @ORM\JoinColumn(nullable=true, referencedColumnName="theodia_church_id")
     */
    private $theodiaChurch;

    /**
     * @var string|null URL of the item
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/url")
     * @Groups("church")
     */
    private $massesUrl;

    /**
     * @var array
     *
     * @ORM\OneToMany(targetEntity="Calendar", mappedBy="church")
     * @Groups("church")
     * @MaxDepth(1)
     **/
    private $calendars;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return array $calendars
     */
    public function getCalendars()
    {
        return $this->calendars;
    }

    /**
     * @param TheodiaChurch $theodiaChurch
     */
    public function setTheodiaChurch($theodiaChurch)
    {
        $this->theodiaChurch = $theodiaChurch;
    }

    /**
     * @param array $calendars
     */
    public function setCalendars($calendars)
    {
        $this->calendars = $calendars;
    }

    public function setWikidataChurch(WikidataChurch $wikidataChurch): void
    {
        $this->wikidataChurch = $wikidataChurch;
    }

    public function getWikidataChurch(): ?WikidataChurch
    {
        return $this->wikidataChurch;
    }

    public function setTheodiaChurchId(TheodiaChurch $theodia): void
    {
        $this->theodiaChurch = $theodiaChurch;
    }

    public function getTheodiaChurch(): ?TheodiaChurch
    {
        return $this->theodiaChurch;
    }

    public function setMassesUrl(?string $massesUrl): void
    {
        $this->massesUrl = $massesUrl;
    }

    public function getMassesUrl(): ?string
    {
        return $this->massesUrl;
    }
}
