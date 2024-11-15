<?php

declare(strict_types=1);

namespace App\Community\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use App\Community\Domain\Exception\CommunityNotFoundException;
use App\Community\Domain\Model\Community;
use App\Community\Infrastructure\ApiPlatform\Payload\CreateCommunityPayload;
use App\Community\Infrastructure\ApiPlatform\Payload\UpdateCommunityPayload;
use App\Community\Infrastructure\ApiPlatform\State\Processor\CreateCommunityProcessor;
use App\Community\Infrastructure\ApiPlatform\State\Processor\UpdateCommunityProcessor;
use App\Community\Infrastructure\ApiPlatform\State\Provider\CommunityCollectionProvider;
use App\Community\Infrastructure\ApiPlatform\State\Provider\CommunityItemProvider;
use App\Field\Infrastructure\ApiPlatform\Filter\FieldTypeFilter;
use App\Field\Infrastructure\ApiPlatform\Filter\FieldWikidataIdFilter;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\UuidV7;

#[ApiResource(
    shortName: 'Community',
    exceptionToStatus: [
        CommunityNotFoundException::class => 404,
    ],
    operations: [
        new Post(
            uriTemplate: '/communities',
            status: 202,
            input: CreateCommunityPayload::class,
            processor: CreateCommunityProcessor::class,
            normalizationContext: ['groups' => ['communities']]
        ),
        new Patch(
            uriTemplate: '/communities',
            status: 200,
            input: UpdateCommunityPayload::class,
            provider: CommunityItemProvider::class,
            processor: UpdateCommunityProcessor::class,
            normalizationContext: ['groups' => ['communities']],
        ),
        new GetCollection(
            filters: [
                FieldTypeFilter::class,
                FieldWikidataIdFilter::class,
            ],
            provider: CommunityCollectionProvider::class,
            normalizationContext: ['groups' => ['communities']]
        ),
        new Get(
            provider: CommunityItemProvider::class,
        ),
    ],
)]

final class CommunityResource
{
    public function __construct(
        #[ApiProperty(identifier: true, readable: true, writable: false)]
        #[Groups(['communities'])]
        public UuidV7 $id,

        /** @var Collection|Field[] $fields */
        #[Groups(['communities'])]
        public Collection $fields,
    ) {}

    public static function fromModel(Community $community): self
    {
        return new self(
            $community->id,
            $community->fields,
        );
    }
}