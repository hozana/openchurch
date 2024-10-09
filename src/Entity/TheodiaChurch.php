<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The most generic type of item.
 *
 * @see http://schema.org/Thing Documentation on Schema.org
 *
 * @ORM\Entity
 *
 * @ORM\Table(name="theodia_churches")
 */
class TheodiaChurch
{
    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @ORM\Column(type="integer", name="theodia_church_id")
     */
    private ?int $id = null;

    /**
     * @ORM\OneToMany(targetEntity="Photo", mappedBy="theodiaChurch")
     **/
    private Collection $photos;

    /**
     * @ORM\OneToMany(targetEntity="Church", mappedBy="theodiaChurch")
     **/
    private Collection $churches;

    /**
     * @ORM\Column(type="datetime")
     *
     * @Assert\DateTime
     *
     * @Assert\NotNull
     */
    private ?\DateTimeInterface $createdAt = null;

    /**
     * @ORM\Column(type="datetime")
     *
     * @Assert\DateTime
     *
     * @Assert\NotNull
     */
    private ?\DateTimeInterface $updatedAt = null;

    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function getChurches(): Collection
    {
        return $this->churches;
    }

    public function setPhotos(Collection $photos): self
    {
        $this->photos = $photos;

        return $this;
    }

    public function setChurches(Collection $churches): self
    {
        $this->churches = $churches;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
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
