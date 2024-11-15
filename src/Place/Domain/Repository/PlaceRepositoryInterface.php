<?php

declare(strict_types=1);

namespace App\Place\Domain\Repository;

use App\Place\Domain\Model\Place;
use App\Shared\Domain\Repository\RepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends RepositoryInterface<Place>
 */
interface PlaceRepositoryInterface extends RepositoryInterface
{
    public function ofId(Uuid $placeid): ?Place;

    public function add(Place $place): void;
}