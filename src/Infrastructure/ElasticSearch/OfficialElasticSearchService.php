<?php

namespace App\Infrastructure\ElasticSearch;

use App\Community\Domain\Enum\CommunityIndex;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Promise\Promise;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

class OfficialElasticSearchService
{
    private Client $elasticsearchClient;

    public function __construct(string $elasticsearchHost)
    {
        $this->elasticsearchClient = ClientBuilder::create()
            ->setHosts([$elasticsearchHost])
            ->setSSLVerification(false)
            ->build();
    }

    private function getSettings() {
        return [
                'number_of_shards' => 1, // Only one shard per index, since we don't face performance issue yet
                'number_of_replicas' => 0, // No replica of shard, since it's a mono-node cluster for the moment
                'analysis' => [
                    'filter' => [
                        'french_stemmer' => [
                            'type' => 'stemmer',
                            'language' => 'light_french',
                        ],
                        'french_stop' => [ //The default stopwords can be overridden with the stopwords or stopwords_path parameters.
                            "type" => "stop",
                            "stopwords" =>  "_french_" ,
                        ],
                    ],
                    'analyzer' => [
                        'default' => [
                            'tokenizer' => 'standard',
                            'filter' => [
                                'french_stemmer',
                                "french_stop",
                            ],
                        ],
                    ],
                ],
        ];
    }

    private function getParishMapping() {
        return [
            'dynamic' => 'strict', // We do not allow other fields than the following
            'properties' => [
                'id' => [
                    'type' => 'text',
                ],
                'parishName' => [
                    'type' => 'text',
                ],
                'dioceseName' => [
                    'type' => 'text',
                ],
            ]
        ];
    }

    public function bulkIndex(string $index, array $ids, array $bodies): void
    {
        if (count($ids) !== count($bodies)) {
            throw new InvalidArgumentException('ids and bodies should be of same size');
        }

        $params = ['body' => []];

        for ($i = 0; $i < count($ids); ++$i) {
            $params['body'][] = [
                'index' => [
                    '_index' => $index,
                    '_id' => $ids[$i],
                ],
            ];

            $params['body'][] = $bodies[$i];

            $this->elasticsearchClient->bulk($params);
            $params = ['body' => []];
        }

        if (!empty($params['body'])) {
            $this->elasticsearchClient->bulk($params);
        }
    }

    public function createIndex(CommunityIndex $index): Elasticsearch|Promise
    {
        $settings = $this->getSettings();

        $body = [
            'settings' => $settings,
        ];

        if ($settings == []) {
            $body = [];
        }

        $params = [
            'index' => $index->value,
            'body' => $body,
        ];

        return $this->elasticsearchClient->indices()->create($params);
    }   

    public function search(CommunityIndex $index, array $body = []): array
    {
        $params = [
            'index' => $index->value,
            'body' => $body,
        ];

        return $this->elasticsearchClient->search($params)->asArray();
    }

    public function existIndex(CommunityIndex $index): bool
    {
        $params = [
            'index' => [$index->value],
        ];

        return $this->elasticsearchClient->indices()->exists($params)->asBool();
    }

    public function deleteIndex(CommunityIndex $index): array
    {
        if (!$this->existIndex($index)) {
            return [];
        }

        $params = [
            'index' => $index->value,
        ];

        return $this->elasticsearchClient->indices()->delete($params)->asArray();
    }

    public function putMapping(CommunityIndex $index): array
    {
        $params = [
            'index' => $index->value,
            'body' => match ($index) {
                CommunityIndex::PARISH => $this->getParishMapping(),
            },
        ];

        return $this->elasticsearchClient->indices()->putMapping($params)->asArray();
    }

    /** @return string[] */
    public function searchParishIds(string $text, int $limit, int $offset): array
    {
        $body = $this->buildQueryForCommunities(
            $text,
            $limit,
            $offset,
        );
        $results = $this->search(CommunityIndex::PARISH, $body);
        // $parishes = $this->extractEntities($results['hits']['hits'], Community::class);
        $globalCount = $results['hits']['total']['value'];

        $entityIds = array_map(static fn (array $hit): string => $hit['_id'], $results['hits']['hits']);
        return $entityIds;
    }

    private function buildQueryForCommunities(string $text, int $limit, int $offset): array
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
                                            'boost' => 10, // Then, in diocese name
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
}
