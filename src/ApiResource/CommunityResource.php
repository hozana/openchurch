<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\ApiResource\Community\CreateCommunityInput;
use App\Processor\CreateCommunityProcessor;
use Symfony\Component\Uid\AbstractUid;

#[ApiResource(
    shortName: 'Community',
    operations: [
        new Post(
            '/communities',
            status: 202,
            input: CreateCommunityInput::class,
            output: false,
            processor: CreateCommunityProcessor::class,
        ),
    ],
)]
final class CommunityResource
{
    public function __construct(
        #[ApiProperty(identifier: true, readable: false, writable: false)]
        public ?AbstractUid $id = null,
    ) {}
}