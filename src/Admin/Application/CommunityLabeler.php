<?php

declare(strict_types=1);

namespace App\Admin\Application;

use App\Field\Domain\Enum\FieldCommunity;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final readonly class CommunityLabeler
{
    public function __construct(
        private CommunityRepositoryInterface $communityRepo,
    ) {
    }

    /**
     * Loads the communities along with their fields, in the order of the given ids
     *
     * @param list<string> $ids
     *
     * @return list<Community>
     */
    public function load(array $ids): array
    {
        $uuids = array_values(array_map(
            Uuid::fromString(...),
            array_filter($ids, Uuid::isValid(...)),
        ));
        if ([] === $uuids) {
            return [];
        }

        return array_values($this->communityRepo->addSelectField()->ofIds($uuids)->asCollection()->toArray());
    }

    /**
     * @param list<string> $ids
     * @param bool         $withParent whether to append the parent name, telling apart the many homonym parishes
     *
     * @return array<string, string> labels indexed by community id, in the order of the given ids
     */
    public function labels(array $ids, bool $withParent = false): array
    {
        $communities = $this->load($ids);
        if ($withParent) {
            // Load the parents and their fields at once, instead of one query per community
            $this->load(array_values(array_filter(array_map(
                static fn (Community $community): ?string => self::parent($community)?->id?->toString(),
                $communities,
            ))));
        }

        $labels = [];
        foreach ($communities as $community) {
            if (null === $community->id) {
                continue;
            }

            $parentName = $withParent && null !== ($parent = self::parent($community)) ? self::name($parent) : null;
            $labels[$community->id->toString()] = self::label($community).(null !== $parentName ? ' — '.$parentName : '');
        }

        return $labels;
    }

    public static function parent(Community $community): ?Community
    {
        $parent = $community->getMostTrustableFieldByName(FieldCommunity::PARENT_COMMUNITY_ID)?->getValue();

        return $parent instanceof Community ? $parent : null;
    }

    public static function name(Community $community): ?string
    {
        $name = $community->getMostTrustableFieldByName(FieldCommunity::NAME)?->getValue();

        return is_string($name) ? $name : null;
    }

    public static function label(Community $community): string
    {
        return self::name($community) ?? $community->id?->toString() ?? '';
    }
}
