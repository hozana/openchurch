<?php

declare(strict_types=1);

namespace App\Field\Infrastructure\ApiPlatform\Payload;

final class FieldPayload
{
    public function __construct(
        public string $source,
        public string $explanation,
        public string $reliability,
        public string $engine,
        public string $name,
        public mixed $value,
    ) {
    }
}