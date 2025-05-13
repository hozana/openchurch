<?php

namespace App\Core\Infrastructure\ElasticSearch\Helper;

use App\Core\Domain\Search\Helper\SearchHelperInterface;
use App\Shared\Domain\Enum\SearchIndex;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Promise\Promise;
use InvalidArgumentException;
use stdClass;

class OfficialElasticSearchHelper implements SearchHelperInterface
{
    private Client $elasticsearchClient;

    public function __construct(string $elasticsearchHost)
    {
        $this->elasticsearchClient = ClientBuilder::create()
            ->setHosts([$elasticsearchHost])
            ->setSSLVerification(false)
            ->build();
    }

    /**
     * @return array<string, mixed>
     */
    private function getSettings(): array
    {
        return [
            'number_of_shards' => 1, // Only one shard per index, since we don't face performance issue yet
            'number_of_replicas' => 0, // No replica of shard, since it's a mono-node cluster for the moment
            'analysis' => [
                'normalizer' => [
                    'french_normalizer' => [
                        'type' => 'custom',
                        'filter' => ['lowercase', 'asciifolding']
                    ]
                ],
                'filter' => [
                    "edge_ngram_filter" => [
                        "type" => "edge_ngram",
                        "min_gram" => 2,
                        "max_gram" => 10,
                    ],
                    'french_stemmer' => [
                        'type' => 'stemmer',
                        'language' => 'light_french',
                    ],
                    'french_stop' => [ // The default stopwords can be overridden with the stopwords or stopwords_path parameters.
                        'type' => 'stop',
                        'stopwords' => '_french_',
                    ],
                    'custom_stop' => [
                        'type' => 'stop',
                        'stopwords' => [
                            'paroisse',
                            'diocese',
                            'sainte',
                            'saint',
                        ],
                    ],
                    'french_elision' => [
                        'type' => 'elision',
                        'articles_case' => true,
                        'articles' => ['l', 'm', 't', 'qu', 'n', 's', 'j', 'd', 'c',
                            'jusqu', 'quoiqu', 'lorsqu', 'puisqu', ],
                    ],
                ],
                'analyzer' => [
                    "edge_ngram_analyzer" => [
                        "tokenizer" => "pattern",
                        "pattern" => "\\W+", // Découpe sur les non-lettres
                        "filter" => ["lowercase", "asciifolding", "edge_ngram_filter"]
                    ],
                    'default' => [
                        'tokenizer' => 'standard',
                        'filter' => [
                            'asciifolding',
                            'lowercase',
                            'custom_stop',
                            'french_stemmer',
                            'french_stop',
                            'french_elision',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getParishMapping(): array
    {
        return [
            'dynamic' => 'strict', // We do not allow other fields than the following
            'properties' => [
                'id' => [
                    'type' => 'keyword',
                ],
                'parishName' => [
                    'type' => 'text',
                    'fields' => [
                        'edge_ngram' => [
                            'type' => 'text',
                            'analyzer' => 'edge_ngram_analyzer',
                        ],
                    ]
                ],
                'dioceseName' => [
                    'type' => 'text',
                    'fields' => [
                        'edge_ngram' => [
                            'type' => 'text',
                            'analyzer' => 'edge_ngram_analyzer',
                        ],
                    ]
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getDioceseMapping(): array
    {
        return [
            'dynamic' => 'strict', // We do not allow other fields than the following
            'properties' => [
                'id' => [
                    'type' => 'keyword',
                ],
                'dioceseName' => [
                    'type' => 'text',
                    'fields' => [
                        'keyword' => [
                            'type' => 'keyword',
                            'normalizer' => 'french_normalizer' // Nouveau normalizer
                        ],
                        'edge_ngram' => [
                            'type' => 'text',
                            'analyzer' => 'edge_ngram_analyzer',
                        ],
                    ]
                ],
            ],
        ];
    }

    /**
     * @param array<mixed>  $bodies
     * @param array<string> $ids
     */
    public function bulkIndex(SearchIndex $index, array $ids, array $bodies): void
    {
        if (count($ids) !== count($bodies)) {
            throw new InvalidArgumentException('ids and bodies should be of same size');
        }

        $params = ['body' => []];

        for ($i = 0; $i < count($ids); ++$i) {
            $params['body'][] = [
                'index' => [
                    '_index' => $index->value,
                    '_id' => $ids[$i],
                ],
            ];

            $params['body'][] = $bodies[$i];

            $this->elasticsearchClient->bulk($params);
            $params = ['body' => []];
        }

        if (count($params['body']) > 0) {
            $this->elasticsearchClient->bulk($params);
        }
    }

    public function createIndex(SearchIndex $index): Elasticsearch|Promise
    {
        $settings = $this->getSettings();

        $body = [
            'settings' => $settings,
        ];

        if ([] == $settings) {
            $body = [];
        }

        $params = [
            'index' => $index->value,
            'body' => $body,
        ];

        return $this->elasticsearchClient->indices()->create($params);
    }

    public function existDocument(SearchIndex $index, string $id): bool
    {
        $params = [
            'index' => $index->value,
            'id' => $id,
        ];

        return $this->elasticsearchClient->exists($params)->asBool();
    }

    public function getDocument(SearchIndex $index, string $id): ?array
    {
        $params = [
            'index' => $index->value,
            'id' => $id,
        ];

        if (!$this->existDocument($index, $id)) {
            return null;
        }

        return $this->elasticsearchClient->get($params)->asArray();
    }

    /**
     * @param array<mixed> $body
     *
     * @return array<mixed>
     */
    public function upsertElement(SearchIndex $index, string $id, array $body): array
    {
        $params = [
            'index' => $index->value,
            'id' => $id,
            'body' => $body,
        ];

        if ($this->existDocument($index, $id)) {
            $params['body'] = [
                'doc' => $body,
            ];

            return $this->elasticsearchClient->update($params)->asArray();
        }

        return $this->elasticsearchClient->index($params)->asArray();
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<mixed>
     */
    public function search(SearchIndex $index, array $body = []): array
    {
        $params = [
            'index' => $index->value,
            'body' => $body,
        ];

        return $this->elasticsearchClient->search($params)->asArray();
    }

    /**
     * @return array<mixed>
     */
    public function all(SearchIndex $index, int $offset, int $limit): array
    {
        $params = [
            'index' => $index->value,
            'body' => [
                'query' => [
                    'match_all' => new stdClass(),
                ],
            ],
            'size' => 100,
            'from' => 0,
        ];

        return $this->elasticsearchClient->search($params)->asArray();
    }

    private function existIndex(SearchIndex $index): bool
    {
        $params = [
            'index' => [$index->value],
        ];

        return $this->elasticsearchClient->indices()->exists($params)->asBool();
    }

    /**
     * @return array<mixed>
     */
    public function deleteIndex(SearchIndex $index): array
    {
        if (!$this->existIndex($index)) {
            return [];
        }

        $params = [
            'index' => $index->value,
        ];

        return $this->elasticsearchClient->indices()->delete($params)->asArray();
    }

    /**
     * @return array<mixed>
     */
    public function putMapping(SearchIndex $index): array
    {
        $params = [
            'index' => $index->value,
            'body' => match ($index) {
                SearchIndex::PARISH => $this->getParishMapping(),
                SearchIndex::DIOCESE => $this->getDioceseMapping(),
            },
        ];

        return $this->elasticsearchClient->indices()->putMapping($params)->asArray();
    }

    public function refresh(SearchIndex $index): void
    {
        $this->elasticsearchClient->indices()->refresh(['index' => [$index->value]]);
    }
}
