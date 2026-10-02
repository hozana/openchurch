<?php

declare(strict_types=1);

namespace App\Admin\Application;

use App\Field\Domain\Enum\FieldCommunity;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Service\SearchServiceInterface;

/**
 * Searches parishes through Elasticsearch, then reads the page of results from the database so that
 * the displayed values are the most trustable ones.
 */
final readonly class ParishSearch
{
    public const int PAGE_SIZE = 20;

    /** Elasticsearch refuses to page beyond its index.max_result_window setting (10,000 by default) */
    private const int MAX_RESULT_WINDOW = 10_000;

    public function __construct(
        private SearchServiceInterface $searchService,
        private CommunityLabeler $communityLabeler,
    ) {
    }

    public function search(string $text, ?string $dioceseId, int $page): ParishSearchPage
    {
        $maxPage = intdiv(self::MAX_RESULT_WINDOW, self::PAGE_SIZE);
        $page = max(1, min($page, $maxPage));

        $result = $this->searchService->searchParishes($text, $dioceseId, self::PAGE_SIZE, ($page - 1) * self::PAGE_SIZE);
        $lastPage = max(1, min((int) ceil($result->total / self::PAGE_SIZE), $maxPage));
        // Beyond the last page (an outdated link, for instance), show the last one
        if ($page > $lastPage) {
            $page = $lastPage;
            $result = $this->searchService->searchParishes($text, $dioceseId, self::PAGE_SIZE, ($page - 1) * self::PAGE_SIZE);
        }

        $parishes = $this->communityLabeler->load($result->ids);
        $this->loadParents($parishes);

        return new ParishSearchPage(
            rows: array_map(self::toRow(...), $parishes),
            total: $result->total,
            page: $page,
            lastPage: $lastPage,
        );
    }

    /**
     * Loads the parent communities with their fields in a single query, instead of one per parish.
     *
     * @param list<Community> $parishes
     */
    private function loadParents(array $parishes): void
    {
        $parentIds = [];
        foreach ($parishes as $parish) {
            $parentId = CommunityLabeler::parent($parish)?->id?->toString();
            if (null !== $parentId) {
                $parentIds[$parentId] = $parentId;
            }
        }

        $this->communityLabeler->load(array_values($parentIds));
    }

    private static function toRow(Community $parish): ParishRow
    {
        $parent = CommunityLabeler::parent($parish);

        return new ParishRow(
            id: $parish->id?->toString() ?? '',
            name: CommunityLabeler::name($parish),
            dioceseName: null !== $parent ? CommunityLabeler::name($parent) : null,
            zipcode: self::string($parish, FieldCommunity::CONTACT_ZIPCODE),
            state: self::string($parish, FieldCommunity::STATE),
            wikidataId: self::int($parish, FieldCommunity::WIKIDATA_ID),
            messesInfoId: self::string($parish, FieldCommunity::MESSESINFO_ID),
        );
    }

    private static function string(Community $community, FieldCommunity $name): ?string
    {
        $value = $community->getMostTrustableFieldByName($name)?->getValue();

        return is_string($value) ? $value : null;
    }

    private static function int(Community $community, FieldCommunity $name): ?int
    {
        $value = $community->getMostTrustableFieldByName($name)?->getValue();

        return is_int($value) ? $value : null;
    }
}
