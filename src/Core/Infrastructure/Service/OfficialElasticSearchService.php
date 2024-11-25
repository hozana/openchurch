<?php

namespace App\Core\Infrastructure\Service;

use App\Core\Domain\Helper\ElasticSearchHelperInterface;
use App\Core\Domain\Service\SearchServiceInterface;
use App\Shared\Domain\Enum\SearchIndex;

class OfficialElasticSearchService implements SearchServiceInterface
{
    public function __construct(
        private ElasticSearchHelperInterface $elasticSearchHelper
    )
    {
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

    private function buildQueryForParishes(string $text, int $limit, int $offset): array
    {
        return [
            'query' => [
                'function_score' => [
                    'query' => [
                        'bool' => [
                            'should' => [
                                [
                                    'match' => [
                                        'parishName' => [
                                            'query' => $text,
                                            'boost' => 20, // First we search in parish name
                                            'fuzziness' => 'AUTO',
                                        ],
                                    ],
                                ],
                                [
                                    'match' => [
                                        'dioceseName' => [
                                            'query' => $text,
                                            'boost' => 50, // Then, in diocese name
                                            'fuzziness' => 'AUTO',
                                        ],
                                    ],
                                ],
                            ],
                            'minimum_should_match' => 1, // At least one of the above rules should contain search text
                        ],
                    ],
                ],
            ],
            'size' => $limit,
            'from' => $offset,
            '_source' => false, // We don't want the source, the _id will be enough
        ];
    }

    private function buildQueryForDioceses(string $text, int $limit, int $offset): array
    {
        return [
            'query' => [
                'bool' => [
                    'should' => [
                        'match' => [
                            'dioceseName' => [
                                'query' => $text,
                                'fuzziness' => 'AUTO',
                            ]
                        ]
                    ],
                    'minimum_should_match' => 1, // At least one of the above rules should contain search text
                ]
            ],
            'size' => $limit,
            'from' => $offset,
            '_source' => false
        ];
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

        $entityIds = array_map(static fn (array $hit): string => $hit['_id'], $results['hits']['hits']);
        return $entityIds;
    }
}
