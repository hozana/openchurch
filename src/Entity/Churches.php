<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The most generic type of item.
 *
 * @see http://schema.org/Thing Documentation on Schema.org
 *
 * @ORM\Entity
 * @ApiResource(iri="http://schema.org/Thing")
 */
class Churches
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $churchId;

    /**
     * @var WikidataChurches
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\WikidataChurches", inversedBy="churches")
     * @ORM\JoinColumn(nullable=false, referencedColumnName="wikidata_church_id")
     * @Assert\NotNull
     */
    private $wikidataChurchId;

    /**
     * @var TheodiaChurches
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\TheodiaChurches", inversedBy="churches")
     * @ORM\JoinColumn(nullable=false, referencedColumnName="theodia_church_id")
     * @Assert\NotNull
     */
    private $theodiaChurchId;

    public function getChurchId(): ?int
    {
        return $this->churchId;
    }

    public function setWikidataChurchId(WikidataChurches $wikidataId): void
    {
        $this->wikidataChurchId = $wikidataChurchId;
    }

    public function getWikidataChurchId(): WikidataChurches
    {
        return $this->wikidataChurchId;
    }

    public function setTheodiaChurchId(TheodiaChurches $theodiaId): void
    {
        $this->theodiaChurchId = $theodiaChurchId;
    }

    public function getTheodiaChurchId(): TheodiaChurches
    {
        return $this->theodiaChurchId;
    }
}
