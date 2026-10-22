<?php

declare(strict_types=1);

namespace App\Admin\Application;

final readonly class ParishRow
{
    /**
     * @param array<string, string> $replacedBy labels of the parishes replacing this one, indexed by id
     */
    public function __construct(
        public string $id,
        public ?string $name,
        public ?string $dioceseName,
        public ?string $zipcode,
        public ?string $state,
        public ?int $wikidataId,
        public ?string $messesInfoId,
        public array $replacedBy = [],
    ) {
    }
}
