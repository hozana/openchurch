<?php

declare(strict_types=1);

namespace App\ApiResource\Community;

use Symfony\Component\Uid\Uuid;

final readonly class CreateCommunityInputField
{
    public function __construct(
        public string $name,
        public string $reliability,
        public string $source,
        public string $explanation,
    ) {
    }
}