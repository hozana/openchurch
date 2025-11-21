<?php

declare(strict_types=1);

namespace App\System\Infrastructure\ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Core\Infrastructure\Redis\RedisClient;
use App\System\Infrastructure\ApiPlatform\Resource\SystemHealthCheckResource;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @implements ProviderInterface<SystemHealthCheckResource>
 */
final readonly class SystemHealthCheckProvider implements ProviderInterface
{
    public function __construct(
        private RedisClient $redisClient,
        private EntityManagerInterface $em,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SystemHealthCheckResource
    {
        $isMySQLUp = false;
        try {
            $this->em->getConnection()->fetchOne('SELECT 1');
            $isMySQLUp = true;
        } catch (ConnectionException) {
        }

        $isRedisUp = (bool) $this->redisClient->client->ping();

        return new SystemHealthCheckResource(
            mysql: $isMySQLUp,
            redis: $isRedisUp,
        );
    }
}
