<?php

declare(strict_types=1);

namespace App\ApiResource\Community;

final readonly class CreateCommunityInputField
{
    public function __construct(
        public string $source,
        public string $explanation,
        public string $reliability,
        public string $name,
        public mixed $value,
    ) {
    }
}