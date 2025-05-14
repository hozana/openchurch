<?php

namespace App\Core\Infrastructure\ElasticSearch\Service;

use App\Core\Domain\Search\Helper\SearchHelperInterface;
use App\Core\Domain\Search\Service\SearchServiceInterface;
use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\Shared\Domain\Enum\SearchIndex;
use Symfony\Component\Uid\Uuid;

class OfficialElasticSearchService implements SearchServiceInterface
{
    public function __construct(
        private SearchHelperInterface $elasticSearchHelper,
        private CommunityRepositoryInterface $communityRepo,
    ) {
    }

    /** @return string[] */
    public function searchParishIds(string $text, int $limit, int $offset): array
    {
        $body = $this->buildQueryForParishes(
            $text,
            $limit,
            $offset,
        );
        $results = $this->elasticSearchHelper->search(SearchIndex::PARISH, $body);

        $entityIds = array_map(static fn (array $hit): string => $hit['_id'], $results['hits']['hits']);

        return $entityIds;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildQueryForParishes(string $text, int $limit, int $offset): array
    {
        $analyzedText = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        $isMoreThan3Words = str_word_count($text) > 2;

        return [
            'query' => [
                'bool' => [
                    'should' => [
                        // 1. Exact match for diocese name (high boost)
                        [
                            'match' => [
                                'dioceseName' => [
                                    'value' => $analyzedText,
                                    'boost' => 100,
                                    'analyzer' => 'french_search_analyzer',
                                ]
                            ]
                        ],
                        // 2. Fuzzy search on diocese (good boost)
                        [
                            'match' => [
                                'dioceseName' => [
                                    'query' => $analyzedText,
                                    'fuzziness' => 'AUTO',
                                    'prefix_length' => 2,
                                    'boost' => 50
                                ]
                            ]
                        ],
                        // 3. Prefix search on parish
                        [
                            'match' => [
                                'parishName' => [
                                    'value' => $analyzedText,
                                    'rewrite' => 'scoring_boolean',
                                    'boost' => $isMoreThan3Words ? 1 : 30,
                                    'analyzer' => 'french_search_analyzer',
                                ]
                            ]
                        ],
                        // 4. Full-text search on parish
                        [
                            'match_phrase_prefix' => [
                                'parishName' => [
                                    'query' => $analyzedText,
                                    'slop' => 10,
                                    'boost' => 20
                                ]
                            ]
                        ],
                        // 5. Fuzzy search on parish
                        [
                            'match' => [
                                'parishName' => [
                                    'query' => $analyzedText,
                                    'fuzziness' => 'AUTO',
                                    'prefix_length' => 2,
                                    'boost' => 10
                                ]
                            ]
                        ]
                    ],
                    'minimum_should_match' => 1
                ]
            ],
            'sort' => [
                ['parishName.french_sort' => ['order' => 'asc']]
            ],
            'size' => $limit,
            'from' => $offset,
            '_source' => false
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildQueryForDioceses(string $text, int $limit, int $offset): array
    {
        $analyzedText = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);

        return [
            'query' => [
                'bool' => [
                    'should' => [
                        // 1. Boosted exact search
                        [
                            'term' => [
                                'dioceseName.keyword' => [
                                    'value' => $analyzedText,
                                    'boost' => 5
                                ]
                            ]
                        ],
                        // 2. Prefix search (short)
                        [
                            'prefix' => [
                                'dioceseName.edge_ngram' => [
                                    'value' => $analyzedText,
                                    'rewrite' => 'scoring_boolean',
                                    'boost' => str_word_count($text) > 2 ? 1 : 3
                                ]
                            ]
                        ],
                        // 3. Full-text search (long)
                        [
                            'match_phrase_prefix' => [
                                'dioceseName' => [
                                    'query' => $analyzedText,
                                    'slop' => 10,
                                    'boost' => 2
                                ]
                            ]
                        ],
                        // 4. Approximate search
                        [
                            'match' => [
                                'dioceseName' => [
                                    'query' => $analyzedText,
                                    'fuzziness' => 'AUTO',
                                    'prefix_length' => 2,
                                    'boost' => 1
                                ]
                            ]
                        ]
                    ],
                    'minimum_should_match' => 1
                ]
            ],
            'sort' => [
                [
                    'dioceseName.french_sort' => [
                        'order' => 'asc'
                    ]
                ]
            ],
            'size' => $limit,
            'from' => $offset,
            '_source' => false,  // We don't want the source, the _id will be enough
        ];
    }

    public function findParish(string $id): ?Community
    {
        $document = $this->elasticSearchHelper->getDocument(SearchIndex::PARISH, $id);
        if ($document) {
            return $this->communityRepo->ofId(Uuid::fromString($document['id']));
        }

        return null;
    }

    public function findDiocese(string $id): ?Community
    {
        $document = $this->elasticSearchHelper->getDocument(SearchIndex::DIOCESE, $id);
        if ($document) {
            return $this->communityRepo->ofId(Uuid::fromString($document['id']));
        }

        return null;
    }

    /** @return string[] */
    public function searchDioceseIds(string $text, int $limit, int $offset): array
    {
        $body = $this->buildQueryForDioceses(
            $text,
            $limit,
            $offset,
        );
        $results = $this->elasticSearchHelper->search(SearchIndex::DIOCESE, $body);

        $entityIds = array_unique(array_map(static fn (array $hit): string => $hit['_id'], $results['hits']['hits']));

        return $entityIds;
    }

    /** @return string[] */
    public function allParishes(?int $limit = 100, ?int $offset = 0): array
    {
        $results = $this->elasticSearchHelper->all(SearchIndex::PARISH, $offset, $limit);

        $entityIds = array_map(static fn (array $hit): string => $hit['_id'], $results['hits']['hits']);

        return $entityIds;
    }

    /** @return string[] */
    public function allDioceses(?int $limit = 100, ?int $offset = 0): array
    {
        $results = $this->elasticSearchHelper->all(SearchIndex::DIOCESE, $offset, $limit);

        $entityIds = array_map(static fn (array $hit): string => $hit['_id'], $results['hits']['hits']);

        return $entityIds;
    }
}
