<?php

declare(strict_types=1);

namespace App\Place\Infrastructure\Doctrine;

use App\Place\Domain\Model\Place;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Place::class)]
#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Place::class)]
final class DoctrinePlaceListener
{
    public function postUpdate(Place $place, PostUpdateEventArgs $event): void
    {
        dd($place);
    }

    public function postPersist(Place $place, PostUpdateEventArgs $event): void
    {
        dd('POST persist');
    }
}