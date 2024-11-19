<?php

declare(strict_types=1);

namespace App\Place\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Get;
use App\Field\Domain\Model\Field;
use App\Place\Domain\Model\Place;
use App\Place\Infrastructure\ApiPlatform\State\Processor\CreatePlaceProcessor;
use App\Place\Infrastructure\ApiPlatform\State\Processor\UpdatePlaceProcessor;
use App\Place\Infrastructure\ApiPlatform\State\Provider\PlaceItemProvider;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'Place',
    operations: [
        new Post(
            security: 'is_granted("ROLE_AGENT")',
            uriTemplate: '/places',
            status: 200,
            processor: CreatePlaceProcessor::class,
            normalizationContext: ['groups' => ['places']]
        ),
        new Patch(
            securityPostDenormalize: 'is_granted("ROLE_AGENT")',
            uriTemplate: '/places/{id}',
            status: 200,
            provider: PlaceItemProvider::class,
            processor: UpdatePlaceProcessor::class,
            output: Field::class,
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
        #[Groups(['places'])]
        #[ApiProperty(identifier: true, readable: true, writable: false)]
        public ?Uuid $id = null,

        #[Groups(['places'])]
        /** @var Field[] $fields */
        public array $fields = [],
    ) {}

    public static function fromModel(Place $place) {
        return new self(
            $place->id,
            $place->fields->toArray(),
        );
    }
}