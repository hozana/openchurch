<?php

declare(strict_types=1);

namespace Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A structured value providing information about the opening hours of a place or a certain service inside a place.\\n\\n The place is \_\_open\_\_ if the \[\[opens\]\] property is specified, and \_\_closed\_\_ otherwise.\\n\\nIf the value for the \[\[closes\]\] property is less than the value for the \[\[opens\]\] property then the hour range is assumed to span over the next day.
 *
 * @see http://schema.org/OpeningHoursSpecification Documentation on Schema.org
 *
 * @ORM\Entity
 * @ApiResource(iri="http://schema.org/OpeningHoursSpecification")
 */
class OpeningHoursSpecification
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
     * @var \DateTimeInterface the opening hour of the place or service on the given day(s) of the week
     *
     * @ORM\Column(type="time")
     * @ApiProperty(iri="http://schema.org/opens")
     * @Assert\Time
     * @Assert\NotNull
     */
    private $open;

    /**
     * @var \DateTimeInterface the closing hour of the place or service on the given day(s) of the week
     *
     * @ORM\Column(type="time")
     * @ApiProperty(iri="http://schema.org/closes")
     * @Assert\Time
     * @Assert\NotNull
     */
    private $close;

    /**
     * @var string[] the day of the week for which these opening hours are valid
     *
     * @ORM\Column(type="simple_array")
     * @ApiProperty(iri="http://schema.org/dayOfWeek")
     * @Assert\NotNull
     * @Assert\Choice(callback={"DayOfWeek", "toArray"}, multiple=true)
     */
    private $dayOfWeeks = [];

    /**
     * @var \DateTimeInterface|null the date when the item becomes valid
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/validFrom")
     * @Assert\DateTime
     */
    private $validFrom;

    /**
     * @var \DateTimeInterface|null The date after when the item is not valid. For example the end of an offer, salary period, or a period of opening hours.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/validThrough")
     * @Assert\DateTime
     */
    private $validThrough;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setOpen(\DateTimeInterface $open): void
    {
        $this->open = $open;
    }

    public function getOpen(): \DateTimeInterface
    {
        return $this->open;
    }

    public function setClose(\DateTimeInterface $close): void
    {
        $this->close = $close;
    }

    public function getClose(): \DateTimeInterface
    {
        return $this->close;
    }

    public function addDayOfWeek($dayOfWeek): void
    {
        $this->dayOfWeeks[] = (string) $dayOfWeek;
    }

    public function removeDayOfWeek(string $dayOfWeek): void
    {
        $key = array_search((string) $dayOfWeek, $this->dayOfWeeks, true);
        if (false !== $key) {
            unset($this->dayOfWeeks[$key]);
        }
    }

    public function getDayOfWeeks(): Collection
    {
        return $this->dayOfWeeks;
    }

    public function setValidFrom(?\DateTimeInterface $validFrom): void
    {
        $this->validFrom = $validFrom;
    }

    public function getValidFrom(): ?\DateTimeInterface
    {
        return $this->validFrom;
    }

    public function setValidThrough(?\DateTimeInterface $validThrough): void
    {
        $this->validThrough = $validThrough;
    }

    public function getValidThrough(): ?\DateTimeInterface
    {
        return $this->validThrough;
    }
}
