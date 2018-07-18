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
     * @ORM\OneToOne(targetEntity="App\Entity\")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull
     */
    private $code_insee;

    /**
     * @var string|null the name of the item
     *
     * @ORM\OneToOne(targetEntity="App\Entity\")
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\OneToOne(targetEntity="App\Entity\")
     * @ORM\JoinColumn(nullable=false)
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
     * @ORM\OneToOne(targetEntity="App\Entity\")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull
     */
    private $commonsCategory;

    /**
     * @var float
     *
     * @ORM\OneToOne(targetEntity="App\Entity\")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull
     */
    private $latitude;

    /**
     * @var float
     *
     * @ORM\OneToOne(targetEntity="App\Entity\")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull
     */
    private $longitude;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param string $code_insee
     */
    public function setCode_insee($code_insee): void
    {
        $this->code_insee = $code_insee;
    }

    /**
     * @return string
     */
    public function getCode_insee()
    {
        return $this->code_insee;
    }

    /**
     * @param string|null $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $searchable
     */
    public function setSearchable($searchable): void
    {
        $this->searchable = $searchable;
    }

    /**
     * @return string
     */
    public function getSearchable()
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

    /**
     * @param string $commonsCategory
     */
    public function setCommonsCategory($commonsCategory): void
    {
        $this->commonsCategory = $commonsCategory;
    }

    /**
     * @return string
     */
    public function getCommonsCategory()
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
