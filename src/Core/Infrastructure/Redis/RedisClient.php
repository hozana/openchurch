<?php

namespace App\Core\Infrastructure\Redis;

use Predis\Client;

class RedisClient
{
    public function __construct(
        public Client $client,
    ) {
    }

    /**
     * Redis hashes only ever hold string values.
     *
     * @return array<string, string>
     */
    public function getHash(string $key): array
    {
        /** @var array<string, string> $hash */
        $hash = $this->client->hgetall($key);

        return $hash;
    }
}
