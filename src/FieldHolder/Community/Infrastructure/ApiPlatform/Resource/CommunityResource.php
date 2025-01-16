<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Infrastructure\ApiPlatform\Input\CommunityWikidataInput;
use App\FieldHolder\Community\Infrastructure\ApiPlatform\State\Processor\CreateCommunityProcessor;
use App\FieldHolder\Community\Infrastructure\ApiPlatform\State\Processor\UpdateCommunityProcessor;
use App\FieldHolder\Community\Infrastructure\ApiPlatform\State\Processor\UpsertCommunityProcessor;
use App\FieldHolder\Community\Infrastructure\ApiPlatform\State\Provider\CommunityCollectionProvider;
use App\FieldHolder\Community\Infrastructure\ApiPlatform\State\Provider\CommunityItemProvider;
use App\Field\Domain\Model\Field;
use App\Field\Infrastructure\ApiPlatform\Filter\FieldNameFilter;
use App\Field\Infrastructure\ApiPlatform\Filter\FieldTypeFilter;
use App\Field\Infrastructure\ApiPlatform\Filter\FieldWikidataIdFilter;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'Community',
    cacheHeaders: [
        'public' => true,
        'max_age' => 3600,
    ],
    operations: [
        new Post(
            security: 'is_granted("ROLE_AGENT")',
            uriTemplate: '/communities',
            status: 200,
            processor: CreateCommunityProcessor::class,
            normalizationContext: ['groups' => ['communities']],
        ),
        new Patch(
            securityPostDenormalize: 'is_granted("ROLE_AGENT")',
            uriTemplate: '/communities/{id}',
            status: 200,
            provider: CommunityItemProvider::class,
            processor: UpdateCommunityProcessor::class,
            normalizationContext: ['groups' => ['communities']],
        ),
        new Put(
            security: 'is_granted("ROLE_AGENT")',
            uriTemplate: '/communities/upsert',
            status: 200,
            processor: UpsertCommunityProcessor::class,
            input: CommunityWikidataInput::class,
        ),
        new GetCollection(
            filters: [
                FieldTypeFilter::class,
                FieldWikidataIdFilter::class,
                FieldNameFilter::class,
            ],
            provider: CommunityCollectionProvider::class,
            normalizationContext: ['groups' => ['communities']],
        ),
        new Get(
            provider: CommunityItemProvider::class,
            normalizationContext: ['groups' => ['communities']],
        ),
    ],
)]
final class CommunityResource
{
    /**
     * @param Field[] $fields
     */
    public function __construct(
        #[Groups(['communities'])]
        #[ApiProperty(identifier: true, readable: true, writable: false)]
        public ?Uuid $id = null,

        #[Groups(['communities'])]
        public array $fields = [],
    ) {
    }

    public static function fromModel(Community $community): self
    {
        return new self(
            $community->id,
            $community->fields->toArray(),
        );
    }
}
