<?php

declare(strict_types=1);

namespace App\Admin\Application;

final readonly class ParishRow
{
    public function __construct(
        public string $id,
        public ?string $name,
        public ?string $dioceseName,
        public ?string $zipcode,
        public ?int $wikidataId,
        public ?string $messesInfoId,
    ) {
    }
}
