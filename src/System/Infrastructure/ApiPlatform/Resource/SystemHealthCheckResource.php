<?php

declare(strict_types=1);

namespace App\System\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\System\Infrastructure\ApiPlatform\State\Provider\SystemHealthCheckProvider;

#[ApiResource(
    shortName: 'System',
    operations: [
        new Get(
            uriTemplate: '/system/health-check',
            provider: SystemHealthCheckProvider::class,
        ),
    ],
)]
final class SystemHealthCheckResource
{
    public function __construct(
        public bool $mysql = false,

        public bool $redis = false,
    ) {
    }
}
