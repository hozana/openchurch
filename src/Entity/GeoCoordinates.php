<?php

declare(strict_types=1);

namespace Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * The geographic coordinates of a place or event.
 *
 * @see http://schema.org/GeoCoordinates Documentation on Schema.org
 *
 * @ORM\Entity
 * @ApiResource(iri="http://schema.org/GeoCoordinates")
 */
class GeoCoordinates
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
     * @var float|null The latitude of a location. For example ```37.42242``` (\[WGS 84\](https://en.wikipedia.org/wiki/World\_Geodetic\_System)).
     *
     * @ORM\Column(type="float", nullable=true)
     * @ApiProperty(iri="http://schema.org/latitude")
     */
    private $latitude;

    /**
     * @var float|null The longitude of a location. For example ```-122.08585``` (\[WGS 84\](https://en.wikipedia.org/wiki/World\_Geodetic\_System)).
     *
     * @ORM\Column(type="float", nullable=true)
     * @ApiProperty(iri="http://schema.org/longitude")
     */
    private $longitude;

    public function getId(): ?int
    {
        return $this->id;
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
