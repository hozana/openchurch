<?php

namespace App\FieldHolder\Community\Infrastructure\ElasticSearch;

use App\FieldHolder\Community\Domain\Service\SearchHelperInterface;
use App\Shared\Domain\Enum\SearchIndex;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Promise\Promise;
use InvalidArgumentException;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

class OfficialElasticSearchHelper implements SearchHelperInterface
{
    private readonly Client $elasticsearchClient;

    public function __construct(string $elasticsearchHost)
    {
        $this->elasticsearchClient = ClientBuilder::create()
            ->setHosts([$elasticsearchHost])
            ->setSSLVerification(false)
            ->build()
        ;
    }

    /**
     * The client is built in synchronous mode: its calls always return a concrete response,
     * never a Promise. This assertion makes that guarantee explicit so that the asArray()/asBool()
     * methods are reachable.
     */
    private function sync(Elasticsearch|Promise $response): Elasticsearch
    {
        if (!$response instanceof Elasticsearch) {
            throw new InvalidArgumentException('The Elasticsearch client is expected to run in synchronous mode.');
        }

        return $response;
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
                        'filter' => ['lowercase', 'asciifolding'],
                    ],
                ],
                'filter' => [
                    'french_stemmer' => [
                        'type' => 'stemmer',
                        'language' => 'light_french',
                    ],
                    'french_stop' => [ // The default stopwords can be overridden with the stopwords or stopwords_path parameters.
                        'type' => 'stop',
                        'stopwords' => [
                            'saint', 'sainte', 'paroisse', 'diocese', 'archidiocese',
                            'au', 'aux', 'avec', 'ce', 'ces', 'dans', 'de', 'des', 'du', 'elle', 'en', 'et',
                            'eux', 'il', 'je', 'le', 'leur', 'lui', 'ma', 'même', 'mes', 'moi', 'mon',
                            'ne', 'nos', 'nous', 'on', 'ou', 'par', 'pas', 'pour', 'qu', 'que', 'qui', 'sa',
                            'se', 'ses', 'son', 'ta', 'te', 'tes', 'toi', 'ton', 'tu', 'un', 'une',
                            'vos', 'votre', 'vous', 'c', 'd', 'j', 'l', 'à', 'm', 'n', 's', 't', 'y', 'été',
                            'étée', 'étées', 'étés', 'étant', 'suis', 'es', 'est', 'sommes', 'êtes', 'sont',
                            'serai', 'seras', 'sera', 'serons', 'serez', 'seront', 'serais', 'serait',
                            'serions', 'seriez', 'seraient', 'étais', 'était', 'étions', 'étiez', 'étaient',
                            'fus', 'fut', 'fûmes', 'fûtes', 'furent', 'sois', 'soit', 'soyons', 'soyez',
                            'soient', 'fusse', 'fusses', 'fût', 'fussions', 'fussiez', 'fussent', 'ayant',
                            'eu', 'eue', 'eues', 'eus', 'ai', 'as', 'avons', 'avez', 'ont', 'aurai', 'auras',
                            'aura', 'aurons', 'aurez', 'auront', 'aurais', 'aurait', 'aurions', 'auriez',
                            'auraient', 'avais', 'avait', 'avions', 'aviez', 'avaient', 'eut', 'eûmes',
                            'eûtes', 'eurent', 'aie', 'aies', 'ait', 'ayons', 'ayez', 'aient', 'eusse',
                            'eusses', 'eût', 'eussions', 'eussiez', 'eussent',
                        ], // Full french list without 'notre' (usefull for Notre-Dame-...)
                        'ignore_case' => true,
                    ],
                    'french_elision' => [
                        'type' => 'elision',
                        'articles_case' => true,
                        'articles' => ['l', 'm', 't', 'qu', 'n', 's', 'j', 'd', 'c',
                            'jusqu', 'quoiqu', 'lorsqu', 'puisqu', ],
                    ],
                ],
                'analyzer' => [
                    'french_search_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => [
                            'lowercase',
                            'asciifolding',
                            'french_elision',
                            'french_stop',
                        ],
                    ],
                    'edge_ngram_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'edge_ngram_tokenizer',
                        'filter' => [
                            'asciifolding',
                            'lowercase',
                            'french_elision',
                            'french_stop',
                            'french_stemmer',
                        ],
                    ],
                    'exact_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'keyword',
                        'filter' => [
                            'lowercase',
                            'asciifolding',
                        ],
                    ],
                ],
                'tokenizer' => [
                    'edge_ngram_tokenizer' => [
                        'type' => 'edge_ngram',
                        'min_gram' => 2,
                        'max_gram' => 15,
                        'token_chars' => ['letter', 'digit'],
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
                    'analyzer' => 'edge_ngram_analyzer',
                    'search_analyzer' => 'french_search_analyzer',
                    'fields' => [
                        'french_sort' => [
                            'type' => 'icu_collation_keyword',
                            'language' => 'fr',
                            'country' => 'FR',
                            'strength' => 'secondary',
                        ],
                        'exact' => [
                            'type' => 'text',
                            'analyzer' => 'exact_analyzer',
                        ],
                        'edge_ngram' => [
                            'type' => 'text',
                            'analyzer' => 'edge_ngram_analyzer',
                        ],
                    ],
                ],
                'dioceseName' => [
                    'type' => 'text',
                    'search_analyzer' => 'french_search_analyzer',
                    'fields' => [
                        'edge_ngram' => [
                            'type' => 'text',
                            'analyzer' => 'edge_ngram_analyzer',
                        ],
                        'exact' => [
                            'type' => 'text',
                            'analyzer' => 'exact_analyzer',
                        ],
                    ],
                ],
                'dioceseId' => [
                    'type' => 'keyword',
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
            'dynamic' => 'strict',
            'properties' => [
                'id' => ['type' => 'keyword'],
                'dioceseName' => [
                    'type' => 'text',
                    'analyzer' => 'edge_ngram_analyzer',
                    'search_analyzer' => 'french_search_analyzer',
                    'fields' => [
                        'edge_ngram' => [
                            'type' => 'text',
                            'analyzer' => 'edge_ngram_analyzer',
                        ],
                        'exact' => [
                            'type' => 'text',
                            'analyzer' => 'exact_analyzer',
                        ],
                        'french_sort' => [
                            'type' => 'icu_collation_keyword',
                            'language' => 'fr',
                            'country' => 'FR',
                            'strength' => 'secondary',
                        ],
                    ],
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

        $bodies = array_values($bodies);
        $documents = [];
        foreach (array_values($ids) as $i => $id) {
            $body = $bodies[$i];
            if (!is_array($body)) {
                throw new InvalidArgumentException('bodies should be arrays');
            }
            /** @var array<string, mixed> $body */
            $documents[] = ['index' => $index, 'id' => (string) $id, 'body' => $body];
        }

        $this->bulkWrite($documents);
    }

    public function bulkWrite(array $documents, array $deletions = []): void
    {
        $operations = [];
        foreach ($documents as $document) {
            $operations[] = ['index' => ['_index' => $document['index']->value, '_id' => $document['id']]];
            $operations[] = $document['body'];
        }
        foreach ($deletions as $deletion) {
            $operations[] = ['delete' => ['_index' => $deletion['index']->value, '_id' => $deletion['id']]];
        }

        if ([] === $operations) {
            return;
        }

        // Failures are reported per operation. Deleting a missing document is not one.
        $response = $this->sync($this->elasticsearchClient->bulk(['body' => $operations]))->asArray();
        if (true === ($response['errors'] ?? false)) {
            throw BulkIndexException::fromResponse($response);
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

        return $this->sync($this->elasticsearchClient->exists($params))->asBool();
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

        return $this->sync($this->elasticsearchClient->get($params))->asArray();
    }

    public function deleteDocument(SearchIndex $index, string $id): void
    {
        try {
            $this->elasticsearchClient->delete(['index' => $index->value, 'id' => $id]);
        } catch (ClientResponseException $e) {
            // Nothing to delete: neither the document nor the index exist
            if (Response::HTTP_NOT_FOUND !== $e->getCode()) {
                throw $e;
            }
        }
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

            return $this->sync($this->elasticsearchClient->update($params))->asArray();
        }

        return $this->sync($this->elasticsearchClient->index($params))->asArray();
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

        return $this->sync($this->elasticsearchClient->search($params))->asArray();
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

        return $this->sync($this->elasticsearchClient->search($params))->asArray();
    }

    private function existIndex(SearchIndex $index): bool
    {
        $params = [
            'index' => [$index->value],
        ];

        return $this->sync($this->elasticsearchClient->indices()->exists($params))->asBool();
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

        return $this->sync($this->elasticsearchClient->indices()->delete($params))->asArray();
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

        return $this->sync($this->elasticsearchClient->indices()->putMapping($params))->asArray();
    }

    public function refresh(SearchIndex $index): void
    {
        $this->elasticsearchClient->indices()->refresh(['index' => [$index->value]]);
    }
}
