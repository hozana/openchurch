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

    /**
     * @return array<string, mixed>
     */
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
                            ],
                        ],
                    ],
                    'minimum_should_match' => 1, // At least one of the above rules should contain search text
                ],
            ],
            'size' => $limit,
            'from' => $offset,
            '_source' => false,
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

        $entityIds = array_map(static fn (array $hit): string => $hit['_id'], $results['hits']['hits']);

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
