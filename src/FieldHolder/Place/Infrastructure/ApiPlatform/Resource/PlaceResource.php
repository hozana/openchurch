<?php

declare(strict_types=1);

namespace App\FieldHolder\Place\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Field\Domain\Model\Field;
use App\Field\Infrastructure\ApiPlatform\Filter\FieldParentCommunityIdFilter;
use App\FieldHolder\Place\Domain\Model\Place;
use App\FieldHolder\Place\Infrastructure\ApiPlatform\Input\PlaceWikidataInput;
use App\FieldHolder\Place\Infrastructure\ApiPlatform\State\Processor\CreatePlaceProcessor;
use App\FieldHolder\Place\Infrastructure\ApiPlatform\State\Processor\UpdatePlaceProcessor;
use App\FieldHolder\Place\Infrastructure\ApiPlatform\State\Processor\UpsertPlaceProcessor;
use App\FieldHolder\Place\Infrastructure\ApiPlatform\State\Provider\PlaceCollectionProvider;
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
            uriTemplate: '/places',
            status: 200,
            normalizationContext: ['groups' => ['places']],
            security: 'is_granted("ROLE_AGENT")',
            processor: CreatePlaceProcessor::class
        ),
        new Patch(
            uriTemplate: '/places/{id}',
            status: 200,
            normalizationContext: ['groups' => ['places']],
            securityPostDenormalize: 'is_granted("ROLE_AGENT")',
            provider: PlaceItemProvider::class,
            processor: UpdatePlaceProcessor::class,
        ),
        new Get(
            normalizationContext: ['groups' => ['places']],
            provider: PlaceItemProvider::class,
        ),
        new Put(
            uriTemplate: '/places/upsert',
            status: 200,
            security: 'is_granted("ROLE_AGENT")',
            input: PlaceWikidataInput::class,
            processor: UpsertPlaceProcessor::class,
        ),
        new GetCollection(
            uriTemplate: '/places',
            normalizationContext: ['groups' => ['places']],
            filters: [
                FieldParentCommunityIdFilter::class,
            ],
            provider: PlaceCollectionProvider::class,
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

    public static function fromModel(Place $place): self
    {
        return new self(
            $place->id,
            $place->fields->toArray(),
        );
    }
}
