<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\ApiResource\Place\CreatePlaceInput;
use App\Processor\CreatePlaceProcessor;
use Symfony\Component\Uid\UuidV7;

#[ApiResource(
    shortName: 'Place',
    operations: [
        new Post(
            '/places',
            status: 202,
            input: CreatePlaceInput::class,
            processor: CreatePlaceProcessor::class,
        ),
    ],
)]
final class PlaceResource
{
    public function __construct(
        #[ApiProperty(identifier: true, readable: true, writable: false)]
        public UuidV7 $id,
    ) {}
}