<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\CalendarType;
use App\Enum\Rite;
use Doctrine\ORM\Mapping as ORM;
use Fresh\DoctrineEnumBundle\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The most generic type of item.
 *
 * @see http://schema.org/Thing Documentation on Schema.org
 *
 * @ORM\Entity
 * @ORM\Table(name="calendars")
 * @ApiResource(iri="http://schema.org/Thing")
 */
class Calendar
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="calendar_id")
     */
    private $id;

    /**
     * @var Church
     *
     * @ORM\ManyToOne(targetEntity="Church", inversedBy="calendars")
     * @ORM\JoinColumn(nullable=false, referencedColumnName="church_id")
     * @Assert\NotNull
     */
    private $church;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     * @Assert\NotNull
     */
    private $calendarUrl;

    /**
     * @ORM\Column(name="rite", type="Rite", nullable=false)
     * @DoctrineAssert\Enum(entity="App\Enum\Rite")
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
     * @ORM\Column(name="type", type="CalendarType", nullable=false)
     * @DoctrineAssert\Enum(entity="App\Enum\CalendarType")
     */
    private $type;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @Assert\NotNull
     */
    private $hozanaUserId;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setChurch(Church $church): void
    {
        $this->church = $church;
    }

    public function getChurch(): Church
    {
        return $this->church;
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
     * @param Rite $rite
     */
    public function setRite($rite): void
    {
        $this->rite = $rite;
    }

    /**
     * @return Rite
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
     * @param CalendarType $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return CalendarType
     */
    public function getType()
    {
        return $this->type;
    }

    public function setHozanaUserId(int $hozanaUserId): void
    {
        $this->hozanaUserId = $hozanaUserId;
    }

    public function getHozanaUserId(): int
    {
        return $this->hozanaUserId;
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
