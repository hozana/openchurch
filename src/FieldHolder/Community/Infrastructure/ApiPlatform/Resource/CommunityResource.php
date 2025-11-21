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
use App\Field\Domain\Model\Field;
use App\Field\Infrastructure\ApiPlatform\Filter\FieldContactZipCodeFilter;
use App\Field\Infrastructure\ApiPlatform\Filter\FieldNameFilter;
use App\Field\Infrastructure\ApiPlatform\Filter\FieldParentWikidataIdFilter;
use App\Field\Infrastructure\ApiPlatform\Filter\FieldTypeFilter;
use App\Field\Infrastructure\ApiPlatform\Filter\FieldWikidataIdFilter;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Infrastructure\ApiPlatform\Input\CommunityWikidataInput;
use App\FieldHolder\Community\Infrastructure\ApiPlatform\State\Processor\CreateCommunityProcessor;
use App\FieldHolder\Community\Infrastructure\ApiPlatform\State\Processor\UpdateCommunityProcessor;
use App\FieldHolder\Community\Infrastructure\ApiPlatform\State\Processor\UpsertCommunityProcessor;
use App\FieldHolder\Community\Infrastructure\ApiPlatform\State\Provider\CommunityCollectionProvider;
use App\FieldHolder\Community\Infrastructure\ApiPlatform\State\Provider\CommunityItemProvider;
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
            uriTemplate: '/communities',
            status: 200,
            normalizationContext: ['groups' => ['communities']],
            security: 'is_granted("ROLE_AGENT")',
            processor: CreateCommunityProcessor::class,
        ),
        new Patch(
            uriTemplate: '/communities/{id}',
            status: 200,
            normalizationContext: ['groups' => ['communities']],
            securityPostDenormalize: 'is_granted("ROLE_AGENT")',
            provider: CommunityItemProvider::class,
            processor: UpdateCommunityProcessor::class,
        ),
        new Put(
            uriTemplate: '/communities/upsert',
            status: 200,
            input: CommunityWikidataInput::class,
            processor: UpsertCommunityProcessor::class,
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['communities']],
            filters: [
                FieldTypeFilter::class,
                FieldWikidataIdFilter::class,
                FieldParentWikidataIdFilter::class,
                FieldNameFilter::class,
                FieldContactZipCodeFilter::class,
            ],
            provider: CommunityCollectionProvider::class,
        ),
        new Get(
            normalizationContext: ['groups' => ['communities']],
            provider: CommunityItemProvider::class,
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
