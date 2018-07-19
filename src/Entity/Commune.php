<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * The most generic type of item.
 *
 * @see http://schema.org/Thing Documentation on Schema.org
 *
 * @ORM\Entity
 * @ApiResource(iri="http://schema.org/Thing")
 */
class Commune
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $codeInsee;

    /**
     * @var string|null the name of the item
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $searchable;

    /**
     * @var Departement|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Departement")
     */
    private $departement;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $commonsCategory;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $latitude;

    /**
     * @var float|null
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $longitude;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCodeInsee(?string $codeInsee): void
    {
        $this->codeInsee = $codeInsee;
    }

    public function getCodeInsee(): ?string
    {
        return $this->codeInsee;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setSearchable(?string $searchable): void
    {
        $this->searchable = $searchable;
    }

    public function getSearchable(): ?string
    {
        return $this->searchable;
    }

    public function setDepartement(?Departement $departement): void
    {
        $this->departement = $departement;
    }

    public function getDepartement(): ?Departement
    {
        return $this->departement;
    }

    public function setCommonsCategory(?string $commonsCategory): void
    {
        $this->commonsCategory = $commonsCategory;
    }

    public function getCommonsCategory(): ?string
    {
        return $this->commonsCategory;
    }

    /**
     * @param float|null $latitude
     */
    public function setLatitude($latitude): void
    {
        $this->latitude = $latitude;
    }

    /**
     * @return float|null
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param float|null $longitude
     */
    public function setLongitude($longitude): void
    {
        $this->longitude = $longitude;
    }

    /**
     * @return float|null
     */
    public function getLongitude()
    {
        return $this->longitude;
    }
}
