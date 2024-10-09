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
 *
 * @ORM\Table(name="calendars")
 *
 * @ApiResource(iri="http://schema.org/Thing")
 */
class Calendar
{
    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @ORM\Column(type="integer", name="calendar_id")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Church", inversedBy="calendars")
     *
     * @ORM\JoinColumn(nullable=false, referencedColumnName="church_id")
     *
     * @Assert\NotNull
     */
    private ?Church $church = null;

    /**
     * @ORM\Column(type="text")
     *
     * @Assert\NotNull
     */
    private string $calendarUrl = '';

    /**
     * @ORM\Column(name="rite", type="Rite", nullable=false)
     *
     * @DoctrineAssert\Enum(entity="App\Enum\Rite")
     */
    private ?string $rite = null;

    /**
     * @ORM\Column(type="text")
     *
     * @Assert\NotNull
     */
    private string $lang = '';

    /**
     * @ORM\Column(name="type", type="CalendarType", nullable=false)
     *
     * @DoctrineAssert\Enum(entity="App\Enum\CalendarType")
     */
    private ?string $type = null;

    /**
     * @ORM\Column(type="integer")
     *
     * @Assert\NotNull
     */
    private ?int $hozanaUserId = null;

    /**
     * @ORM\Column(type="datetime")
     *
     * @Assert\DateTime
     *
     * @Assert\NotNull
     */
    private ?\DateTimeInterface $createdAt = null;

    /**
     * ?   * @ORM\Column(type="datetime")
     *
     * @Assert\DateTime
     *
     * @Assert\NotNull
     */
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setChurch(Church $church): self
    {
        $this->church = $church;

        return $this;
    }

    public function getChurch(): ?Church
    {
        return $this->church;
    }

    public function setCalendarUrl(string $calendarUrl): self
    {
        $this->calendarUrl = $calendarUrl;

        return $this;
    }

    public function getCalendarUrl(): string
    {
        return $this->calendarUrl;
    }

    public function setRite(?string $rite): self
    {
        $this->rite = $rite;

        return $this;
    }

    public function getRite(): ?string
    {
        return $this->rite;
    }

    public function setLang(string $lang): self
    {
        $this->lang = $lang;

        return $this;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setHozanaUserId(int $hozanaUserId): self
    {
        $this->hozanaUserId = $hozanaUserId;

        return $this;
    }

    public function getHozanaUserId(): ?int
    {
        return $this->hozanaUserId;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
}
