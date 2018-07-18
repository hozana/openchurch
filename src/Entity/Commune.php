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
     * @var string
     *
     * @ORM\Column(type="text")
     * @Assert\NotNull
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
     * @var string
     *
     * @ORM\Column(type="text")
     * @Assert\NotNull
     */
    private $searchable;

    /**
     * @var Departement|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Departement")
     */
    private $departement;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     * @Assert\NotNull
     */
    private $commonsCategory;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @Assert\NotNull
     */
    private $latitude;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @Assert\NotNull
     */
    private $longitude;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCodeInsee(string $codeInsee): void
    {
        $this->codeInsee = $codeInsee;
    }

    public function getCodeInsee(): string
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

    public function setSearchable(string $searchable): void
    {
        $this->searchable = $searchable;
    }

    public function getSearchable(): string
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

    public function setCommonsCategory(string $commonsCategory): void
    {
        $this->commonsCategory = $commonsCategory;
    }

    public function getCommonsCategory(): string
    {
        return $this->commonsCategory;
    }

    /**
     * @param float $latitude
     */
    public function setLatitude($latitude): void
    {
        $this->latitude = $latitude;
    }

    /**
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param float $longitude
     */
    public function setLongitude($longitude): void
    {
        $this->longitude = $longitude;
    }

    /**
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }
}
