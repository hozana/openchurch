<?php

namespace App\Helper\Trait;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

trait Timestampable
{
    #[ORM\Column(type: 'datetime_immutable')]
    public DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $updatedAt = null;

    /**
     * Sets updatedAt to now.
     */
    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
