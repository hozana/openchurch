<?php

declare(strict_types=1);

namespace App\Place\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Get;
use App\Place\Domain\Model\Place;
use App\Place\Infrastructure\ApiPlatform\Payload\CreatePlacePayload;
use App\Place\Infrastructure\ApiPlatform\Payload\UpdatePlacePayload;
use App\Place\Infrastructure\ApiPlatform\State\Processor\CreatePlaceProcessor;
use App\Place\Infrastructure\ApiPlatform\State\Processor\UpdatePlaceProcessor;
use App\Place\Infrastructure\ApiPlatform\State\Provider\PlaceItemProvider;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\UuidV7;

#[ApiResource(
    shortName: 'Place',
    operations: [
        new Post(
            security: 'is_granted("ROLE_AGENT")',
            uriTemplate: '/places',
            status: 202,
            input: CreatePlacePayload::class,
            processor: CreatePlaceProcessor::class,
            normalizationContext: ['groups' => ['places']]
        ),
        new Patch(
            securityPostDenormalize: 'is_granted("ROLE_AGENT")',
            uriTemplate: '/places',
            status: 200,
            input: UpdatePlacePayload::class,
            provider: PlaceItemProvider::class,
            processor: UpdatePlaceProcessor::class,
            normalizationContext: ['groups' => ['places']],
        ),
        new Get(
            provider: PlaceItemProvider::class,
        ),
    ],
)]
final class PlaceResource
{
    public function __construct(
        #[ApiProperty(identifier: true, readable: true, writable: false)]
        #[Groups(['places'])]
        public UuidV7 $id,

        /** @var Collection|Field[] $fields */
        #[Groups(['places'])]
        public Collection $fields,
    ) {}

    public static function fromModel(Place $place) {
        return new self(
            $place->id,
            $place->fields,
        );
    }
}