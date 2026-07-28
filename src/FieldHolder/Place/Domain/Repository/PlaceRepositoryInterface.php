<?php

declare(strict_types=1);

namespace App\FieldHolder\Place\Domain\Repository;

use App\FieldHolder\Place\Domain\Model\Place;
use App\Shared\Domain\Repository\RepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends RepositoryInterface<Place>
 */
interface PlaceRepositoryInterface extends RepositoryInterface
{
    public function ofId(Uuid $placeid): ?Place;

    /**
     * @param array<Uuid> $ids
     */
    public function ofIds(array $ids): static;

    public function add(Place $place): void;

    public function addSelectField(): static;

    // The filters below are optional: a null value leaves the query unchanged.

    public function withWikidataId(?int $value): static;

    /**
     * @param array<int>|null $wikidataIds
     */
    public function withWikidataIds(?array $wikidataIds): static;

    public function withParentCommunityId(?Uuid $parentId): static;
}
