<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Application;

use App\Field\Domain\Enum\FieldCommunity;
use App\FieldHolder\Community\Domain\Enum\CommunityType;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Community\Domain\Service\SearchHelperInterface;
use App\Shared\Domain\Enum\SearchIndex;

final readonly class CommunitySearchIndexer
{
    public function __construct(
        private SearchHelperInterface $searchHelper,
        private CommunityRepositoryInterface $communityRepo,
    ) {
    }

    /**
     * Pushes the communities to the index matching their retained type.
     */
    public function index(Community ...$communities): void
    {
        // Indexed by id, to write each document once
        $parishDocuments = [];
        $dioceseDocuments = [];
        $deletions = [];

        foreach ($communities as $community) {
            if (null === $community->id) {
                continue;
            }

            $id = $community->id->toString();
            $parishDocument = $this->parishDocuments([$community]);
            $dioceseDocument = self::dioceseDocuments([$community]);
            $parishDocuments = [...$parishDocuments, ...$parishDocument];
            $dioceseDocuments = [...$dioceseDocuments, ...$dioceseDocument];

            if ([] === $parishDocument) {
                $deletions[] = ['index' => SearchIndex::PARISH, 'id' => $id];
                $children = $this->communityRepo->addSelectField()->withParentCommunityId($community->id);
                $parishDocuments = [...$parishDocuments, ...$this->parishDocuments($children)];
            }
            if ([] === $dioceseDocument) {
                $deletions[] = ['index' => SearchIndex::DIOCESE, 'id' => $id];
            }
        }

        $documents = [];
        foreach ($parishDocuments as $id => $body) {
            $documents[] = ['index' => SearchIndex::PARISH, 'id' => $id, 'body' => $body];
        }
        foreach ($dioceseDocuments as $id => $body) {
            $documents[] = ['index' => SearchIndex::DIOCESE, 'id' => $id, 'body' => $body];
        }

        $this->searchHelper->bulkWrite($documents, $deletions);
    }

    /**
     * The documents of those of the communities whose retained type is parish.
     *
     * @param iterable<Community> $communities
     *
     * @return array<string, array{id: string, parishName: mixed, dioceseId: ?string, dioceseName: mixed}> indexed by id
     */
    public function parishDocuments(iterable $communities): array
    {
        $parishes = [];
        foreach ($communities as $community) {
            if (null !== $community->id && CommunityType::PARISH->value === self::type($community)) {
                $parishes[$community->id->toString()] = $community;
            }
        }
        $this->loadParents($parishes);

        $documents = [];
        foreach ($parishes as $id => $parish) {
            $diocese = self::parentDiocese($parish);
            $documents[$id] = [
                'id' => $id,
                'parishName' => $parish->getMostTrustableFieldByName(FieldCommunity::NAME)?->getValue(),
                'dioceseId' => $diocese?->id?->toString(),
                'dioceseName' => $diocese?->getMostTrustableFieldByName(FieldCommunity::NAME)?->getValue(),
            ];
        }

        return $documents;
    }

    /**
     * The documents of those of the communities whose retained type is diocese.
     *
     * @param iterable<Community> $communities
     *
     * @return array<string, array{id: string, dioceseName: mixed}> indexed by id
     */
    public static function dioceseDocuments(iterable $communities): array
    {
        $documents = [];
        foreach ($communities as $community) {
            if (null !== $community->id && CommunityType::DIOCESE->value === self::type($community)) {
                $id = $community->id->toString();
                $documents[$id] = [
                    'id' => $id,
                    'dioceseName' => $community->getMostTrustableFieldByName(FieldCommunity::NAME)?->getValue(),
                ];
            }
        }

        return $documents;
    }

    /**
     * Loads the parents of the parishes, with their fields, in a single query.
     *
     * @param array<string, Community> $parishes
     */
    private function loadParents(array $parishes): void
    {
        $parentIds = [];
        foreach ($parishes as $parish) {
            $parent = self::parent($parish);
            if (null !== $parent?->id) {
                $parentIds[$parent->id->toString()] = $parent->id;
            }
        }

        if ([] !== $parentIds) {
            $this->communityRepo->addSelectField()->ofIds(array_values($parentIds))->asCollection();
        }
    }

    private static function type(Community $community): mixed
    {
        return $community->getMostTrustableFieldByName(FieldCommunity::TYPE)?->getValue();
    }

    private static function parent(Community $community): ?Community
    {
        $parent = $community->getMostTrustableFieldByName(FieldCommunity::PARENT_COMMUNITY_ID)?->getValue();

        return $parent instanceof Community ? $parent : null;
    }

    private static function parentDiocese(Community $parish): ?Community
    {
        $parent = self::parent($parish);

        return null !== $parent && CommunityType::DIOCESE->value === self::type($parent) ? $parent : null;
    }
}
