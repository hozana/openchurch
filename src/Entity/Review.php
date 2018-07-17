<?php

declare(strict_types=1);

namespace Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A review of an item - for example, of a restaurant, movie, or store.
 *
 * @see http://schema.org/Review Documentation on Schema.org
 *
 * @ORM\Entity
 * @ApiResource(iri="http://schema.org/Review")
 */
class Review
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
     * @var Church|null the item that is being reviewed/rated
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Church")
     * @ApiProperty(iri="http://schema.org/itemReviewed")
     */
    private $itemReviewed;

    /**
     * @var text
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotNull
     */
    private $reviewAspect;

    /**
     * @var string|null the actual body of the review
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/reviewBody")
     */
    private $reviewBody;

    /**
     * @var number|null The rating given in this review. Note that reviews can themselves be rated. The ```reviewRating``` applies to rating given by the review. The \[\[aggregateRating\]\] property applies to the review itself, as a creative work.
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\")
     * @ApiProperty(iri="http://schema.org/reviewRating")
     */
    private $reviewRating;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setItemReviewed(?Church $itemReviewed): void
    {
        $this->itemReviewed = $itemReviewed;
    }

    public function getItemReviewed(): ?Church
    {
        return $this->itemReviewed;
    }

    /**
     * @param text $reviewAspect
     */
    public function setReviewAspect($reviewAspect): void
    {
        $this->reviewAspect = $reviewAspect;
    }

    /**
     * @return text
     */
    public function getReviewAspect()
    {
        return $this->reviewAspect;
    }

    public function setReviewBody(?string $reviewBody): void
    {
        $this->reviewBody = $reviewBody;
    }

    public function getReviewBody(): ?string
    {
        return $this->reviewBody;
    }

    /**
     * @param number|null $reviewRating
     */
    public function setReviewRating($reviewRating): void
    {
        $this->reviewRating = $reviewRating;
    }

    /**
     * @return number|null
     */
    public function getReviewRating()
    {
        return $this->reviewRating;
    }
}
