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
     * @return array<mixed>
     */
    public function getHash(string $key): array
    {
        return $this->client->hgetall($key);
    }
}
