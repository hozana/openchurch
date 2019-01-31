<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The most generic type of item.
 *
 * @see http://schema.org/Thing Documentation on Schema.org
 *
 * @ORM\Entity
 * @ORM\Table(name="theodia_churches")
 */
class TheodiaChurch
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="theodia_church_id")
     */
    private $id;

    /**
     * @var array
     *
     * @ORM\OneToMany(targetEntity="Photo", mappedBy="theodiaChurch")
     **/
    private $photos;

    /**
     * @var array
     *
     * @ORM\OneToMany(targetEntity="Church", mappedBy="theodiaChurch")
     **/
    private $churches;

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

    /**
     * @return Photo[] $photos
     */
    public function getPhotos()
    {
        return $this->photos;
    }

    /**
     * @return Church[] $churches
     */
    public function getChurches()
    {
        return $this->churches;
    }

    /**
     * @param array $photos
     */
    public function setPhotos($photos)
    {
        $this->photos = $photos;
    }

    /**
     * @param array $churches
     */
    public function setChurches($churches)
    {
        $this->churches = $churches;
    }

    public function getId(): ?int
    {
        return $this->id;
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
