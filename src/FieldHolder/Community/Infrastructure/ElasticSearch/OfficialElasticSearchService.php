<?php

namespace App\FieldHolder\Community\Infrastructure\ElasticSearch;

use App\FieldHolder\Community\Domain\Model\Community;
use App\FieldHolder\Community\Domain\Repository\CommunityRepositoryInterface;
use App\FieldHolder\Community\Domain\Service\SearchHelperInterface;
use App\FieldHolder\Community\Domain\Service\SearchServiceInterface;
use App\Shared\Domain\Enum\SearchIndex;
use stdClass;
use Symfony\Component\Uid\Uuid;

class OfficialElasticSearchService implements SearchServiceInterface
{
    public function __construct(
        private SearchHelperInterface $elasticSearchHelper,
        private CommunityRepositoryInterface $communityRepo,
    ) {
    }

    /** @return string[] */
    public function searchParishIds(string $text, ?string $dioceseId, int $limit, int $offset): array
    {
        $body = $this->buildQueryForParishes(
            $text,
            $dioceseId,
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
    private function buildQueryForParishes(string $text, ?string $dioceseId, int $limit, int $offset): array
    {
        $analyzedText = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);

        if (trim($analyzedText) === '') {
            return [
                'query' => ['match_all' => new stdClass()],
                'sort' => [['parishName.french_sort' => ['order' => 'asc']]],
                'size' => $limit,
                'from' => $offset,
                '_source' => false
            ];
        }

        $query = [
            'query' => [
                'bool' => [
                    'should' => [
                        // 1. Exact match for parish name (high boost)
                        [
                            'match' => [
                                'parishName.exact' => [
                                    'query' => $analyzedText,
                                    'analyzer' => 'exact_analyzer',
                                    'boost' => 5
                                ]
                            ]
                        ],
                        // 2. prefix search search on parish
                        [
                            'prefix' => [
                                'parishName.edge_ngram' => [
                                    'value' => $analyzedText,
                                    'rewrite' => 'scoring_boolean',
                                    'boost' => str_word_count($text) > 2 ? 1 : 3
                                ]
                            ]
                        ],
                        // 3. Approximate search on parish
                        [
                            'match' => [
                                'parishName' => [
                                    'query' => $analyzedText,
                                    'fuzziness' => 'AUTO',
                                    'prefix_length' => 2,
                                    'boost' => 1
                                ]
                            ]
                        ],
                        // 4. exact search on diocese
                        [
                            'match' => [
                                'dioceseName.exact' => [
                                    'query' => $analyzedText,
                                    'analyzer' => 'exact_analyzer'
                                ]
                            ]
                        ],
                        // 5. Prefix search on diocese
                        [
                            'prefix' => [
                                'dioceseName.edge_ngram' => [
                                    'value' => $analyzedText,
                                    'rewrite' => 'scoring_boolean',
                                    'boost' => str_word_count($text) > 2 ? 1 : 3
                                ]
                            ]
                        ],
                        // 6. Approximate search on diocese
                        [
                            'match' => [
                                'dioceseName' => [
                                    'query' => $analyzedText,
                                    'fuzziness' => 'AUTO',
                                    'prefix_length' => 2,
                                ]
                            ]
                        ]
                    ],
                    'minimum_should_match' => 1
                ]
            ],
            'sort' => [
                ['_score' => ['order' => 'desc']],
                ['parishName.french_sort' => ['order' => 'asc']],
            ],
            'size' => $limit,
            'from' => $offset,
            '_source' => false
        ];

        if ($dioceseId !== null) {
            $query['query']['bool']['must'] = [
                [
                    'term' => [
                        'dioceseId' => $dioceseId
                    ]
                ]
            ];
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildQueryForDioceses(string $text, int $limit, int $offset): array
    {
        $analyzedText = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);

        if (trim($analyzedText) === '') {
            return [
                'query' => ['match_all' => new stdClass()],
                'sort' => [['dioceseName.french_sort' => ['order' => 'asc']]],
                'size' => $limit,
                'from' => $offset,
                '_source' => false
            ];
        }

        return [
            'query' => [
                'bool' => [
                    'should' => [
                        // 1. Boosted exact search
                        [
                            'match' => [
                                'dioceseName.exact' => [
                                    'query' => $analyzedText,
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
                        // 3. Approximate search
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
                ['_score' => ['order' => 'desc']],
                ['dioceseName.french_sort' => ['order' => 'asc']],
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
