<?php

declare(strict_types=1);

namespace App\City\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\City\Infrastructure\ApiPlatform\State\Provider\CityCollectionProvider;
use App\City\Infrastructure\ApiPlatform\Filter\NameFilter;
use App\Field\Domain\Model\Field;

#[ApiResource(
    shortName: 'City',
    cacheHeaders: [
        'public' => true,
        'max_age' => 3600,
    ],
    uriTemplate: '/cities',
    operations: [
        new GetCollection(
            filters: [
                NameFilter::class,
            ],
            provider: CityCollectionProvider::class,
        ),
    ],
)]
final class CityResource
{
    /**
     * @param Field[] $fields
     */
    public function __construct(
        public string $name,

        public string $postCode,
    ) {
    }

    public static function fromModel(array $city): self
    {
        return new self(
            $city['cityName'],
            $city['postCode'],
        );
    }
}
