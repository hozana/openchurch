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
 * @ORM\Table(name="churches")
 * @ApiResource(iri="http://schema.org/Thing")
 */
class Church
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="church_id")
     */
    private $id;

    /**
     * @var WikidataChurch
     *
     * @ORM\ManyToOne(targetEntity="WikidataChurch", inversedBy="churches")
     * @ORM\JoinColumn(nullable=false, referencedColumnName="wikidata_church_id")
     * @Assert\NotNull
     */
    private $wikidataChurch;

    /**
     * @var TheodiaChurch
     *
     * @ORM\ManyToOne(targetEntity="TheodiaChurch", inversedBy="churches")
     * @ORM\JoinColumn(nullable=false, referencedColumnName="theodia_church_id")
     * @Assert\NotNull
     */
    private $theodiaChurch;

    /**
     * @var string|null URL of the item
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/url")
     */
    private $massesUrl;

    /**
     * @var array
     *
     * @ORM\OneToMany(targetEntity="Calendar", mappedBy="church")
     **/
    protected $calendars;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setWikidataChurch(WikidataChurch $wikidataChurch): void
    {
        $this->wikidataChurch = $wikidataChurch;
    }

    public function getWikidataChurch(): WikidataChurch
    {
        return $this->wikidataChurch;
    }

    public function setTheodiaChurchId(TheodiaChurch $theodia): void
    {
        $this->theodiaChurch = $theodiaChurch;
    }

    public function getTheodiaChurch(): TheodiaChurch
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
