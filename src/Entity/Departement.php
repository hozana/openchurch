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

/**
 * The most generic type of item.
 *
 * @see http://schema.org/Thing Documentation on Schema.org
 *
 * @ORM\Entity
 * @ApiResource(iri="http://schema.org/Thing")
 * @ApiFilter(SearchFilter::class, properties={"name": "ipartial"})
 * @ApiFilter(OrderFilter::class, properties={"code", "name"}, arguments={"orderParameterName"="order"})
 */
class Departement
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
    private $code;

    /**
     * @var string|null the name of the item
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     */
    private $name;

    /**
     * @var PersistentCollection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Commune", mappedBy="departement")
     * @ApiSubresource()
     */
    private $communes;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $de;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function addCommune(Commune $commune)
    {
        $this->communes[] = $commune;

        return $this;
    }

    public function getCommunes(): PersistentCollection
    {
        return $this->communes;
    }

    public function setDe(?string $de): void
    {
        $this->de = $de;
    }

    public function getDe(): ?string
    {
        return $this->de;
    }
}
