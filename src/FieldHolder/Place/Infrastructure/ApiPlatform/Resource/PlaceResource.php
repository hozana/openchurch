<?php

declare(strict_types=1);

namespace App\FieldHolder\Place\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\FieldHolder\Place\Infrastructure\ApiPlatform\Input\PlaceWikidataInput;
use App\FieldHolder\Place\Infrastructure\ApiPlatform\State\Processor\UpsertPlaceProcessor;
use App\Field\Domain\Model\Field;
use App\FieldHolder\Place\Domain\Model\Place;
use App\FieldHolder\Place\Infrastructure\ApiPlatform\State\Processor\CreatePlaceProcessor;
use App\FieldHolder\Place\Infrastructure\ApiPlatform\State\Processor\UpdatePlaceProcessor;
use App\FieldHolder\Place\Infrastructure\ApiPlatform\State\Provider\PlaceItemProvider;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'Place',
    cacheHeaders: [
        'public' => true,
        'max_age' => 3600,
    ],
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
            normalizationContext: ['groups' => ['places']],
        ),
        new Get(
            provider: PlaceItemProvider::class,
            normalizationContext: ['groups' => ['places']],
        ),
        new Put(
            security: 'is_granted("ROLE_AGENT")',
            uriTemplate: '/places/upsert',
            status: 200,
            processor: UpsertPlaceProcessor::class,
            input: PlaceWikidataInput::class,
        ),
    ],
)]
final class PlaceResource
{
    /**
     * @param Field[] $fields
     */
    public function __construct(
        #[Groups(['places'])]
        #[ApiProperty(identifier: true, readable: true, writable: false)]
        public ?Uuid $id = null,

        #[Groups(['places'])]
        public array $fields = [],
    ) {
    }

    public static function fromModel(Place $place): PlaceResource
    {
        return new self(
            $place->id,
            $place->fields->toArray(),
        );
    }
}
