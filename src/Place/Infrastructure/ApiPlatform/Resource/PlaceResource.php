<?php

declare(strict_types=1);

namespace App\Place\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Place\Infrastructure\ApiPlatform\Payload\CreatePlacePayload;
use App\Place\Infrastructure\ApiPlatform\State\Processor\CreatePlaceProcessor;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\UuidV7;

#[ApiResource(
    shortName: 'Place',
    operations: [
        new Post(
            '/places',
            status: 202,
            input: CreatePlacePayload::class,
            processor: CreatePlaceProcessor::class,
            normalizationContext: ['groups' => ['places']]
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
}