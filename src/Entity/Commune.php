<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;

/**
 * The most generic type of item.
 *
 * @see http://schema.org/Thing Documentation on Schema.org
 *
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false, hardDelete=true)
 * @Gedmo\Loggable
 * @ApiResource(iri="http://schema.org/Thing")
 * @ApiFilter(SearchFilter::class, properties={"id": "exact", "codeInsee": "exact", "name": "ipartial"})
 * @ApiFilter(OrderFilter::class, properties={"codeInsee", "name"}, arguments={"orderParameterName"="order"})
 */
class Commune
{
    use SoftDeleteableEntity;

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
     * @Gedmo\Versioned
     * @ORM\Column(type="text", nullable=true)
     */
    private $codeInsee;

    /**
     * @var string|null the name of the item
     *
     * @Gedmo\Versioned
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $name;

    /**
     * @var string|null
     *
     * @Gedmo\Versioned
     * @ORM\Column(type="text", nullable=true)
     */
    private $searchable;

    /**
     * @var Departement|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Departement", inversedBy="communes")
     */
    private $departement;

    /**
     * @var PersistentCollection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Church", mappedBy="commune")
     * @ApiSubresource()
     */
    private $churches;

    /**
     * @var string|null
     *
     * @Gedmo\Versioned
     * @ORM\Column(type="text", nullable=true)
     */
    private $commonsCategory;

    /**
     * @var float|null
     *
     * @Gedmo\Versioned
     * @ORM\Column(type="float", nullable=true)
     */
    private $latitude;

    /**
     * @var float|null
     *
     * @Gedmo\Versioned
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

    public function addChurch(Church $church)
    {
        $this->churches[] = $church;

        return $this;
    }

    public function getChurches(): PersistentCollection
    {
        return $this->churches;
    }

    public function setCommonsCategory(?string $commonsCategory): void
    {
        $this->commonsCategory = $commonsCategory;
    }

    public function getCommonsCategory(): ?string
    {
        return $this->commonsCategory;
    }

    public function setLatitude($latitude): void
    {
        $this->latitude = $latitude;
    }

    public function getLatitude()
    {
        return $this->latitude;
    }

    public function setLongitude($longitude): void
    {
        $this->longitude = $longitude;
    }

    public function getLongitude()
    {
        return $this->longitude;
    }
}
