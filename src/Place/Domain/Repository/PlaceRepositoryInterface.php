<?php

declare(strict_types=1);

namespace App\Place\Domain\Repository;

use App\Place\Domain\Model\Place;
use App\Shared\Domain\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<Place>
 */
interface PlaceRepositoryInterface extends RepositoryInterface
{
    public function ofId(string $placeid): ?Place;

    public function add(Place $community): void;
}