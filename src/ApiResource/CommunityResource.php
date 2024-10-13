<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\ApiResource\Community\CreateCommunityInput;
use App\Filter\Field\FieldTypeFilter;
use App\Processor\CreateCommunityProcessor;
use App\Provider\CommunityCollectionProvider;
use Symfony\Component\Uid\UuidV7;

#[ApiResource(
    shortName: 'Community',
    operations: [
        new Post(
            '/communities',
            status: 202,
            input: CreateCommunityInput::class,
            processor: CreateCommunityProcessor::class,
        ),
        new GetCollection(
            filters: [FieldTypeFilter::class],
            provider: CommunityCollectionProvider::class,
        ),
    ],
)]
final class CommunityResource
{
    public function __construct(
        #[ApiProperty(identifier: true, readable: true, writable: false)]
        public UuidV7 $id,
    ) {}
}