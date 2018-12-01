<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\CalendarTypes;
use App\Enum\Rites;
use Doctrine\ORM\Mapping as ORM;
use Fresh\DoctrineEnumBundle\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The most generic type of item.
 *
 * @see http://schema.org/Thing Documentation on Schema.org
 *
 * @ORM\Entity
 * @ApiResource(iri="http://schema.org/Thing")
 */
class Calendars
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $calendarId;

    /**
     * @var Churches
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Churches", inversedBy="calendars")
     * @ORM\JoinColumn(nullable=false, referencedColumnName="church_id")
     * @Assert\NotNull
     */
    private $churchId;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     * @Assert\NotNull
     */
    private $calendarUrl;

    /**
     * @ORM\Column(name="rite", type="Rites", nullable=false)
     * @DoctrineAssert\Enum(entity="App\Enum\Rites")
     */
    private $rite;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     * @Assert\NotNull
     */
    private $lang;

    /**
     * @ORM\Column(name="type", type="CalendarTypes", nullable=false)
     * @DoctrineAssert\Enum(entity="App\Enum\CalendarTypes")
     */
    private $type;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(type="datetime")
     * @Assert\DateTime
     * @Assert\NotNull
     */
    private $createdAt;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(type="datetime")
     * @Assert\DateTime
     * @Assert\NotNull
     */
    private $updatedAt;

    public function getCalendarId(): ?int
    {
        return $this->calendarId;
    }

    public function setChurchId(Churches $churchId): void
    {
        $this->churchId = $churchId;
    }

    public function getChurchId(): Churches
    {
        return $this->churchId;
    }

    public function setCalendarUrl(string $calendarUrl): void
    {
        $this->calendarUrl = $calendarUrl;
    }

    public function getCalendarUrl(): string
    {
        return $this->calendarUrl;
    }

    /**
     * @param Rites $rite
     */
    public function setRite($rite): void
    {
        $this->rite = $rite;
    }

    /**
     * @return Rites
     */
    public function getRite()
    {
        return $this->rite;
    }

    public function setLang(string $lang): void
    {
        $this->lang = $lang;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    /**
     * @param CalendarTypes $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return CalendarTypes
     */
    public function getType()
    {
        return $this->type;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }
}
