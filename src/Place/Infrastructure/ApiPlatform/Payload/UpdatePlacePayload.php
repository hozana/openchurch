<?php

declare(strict_types=1);

namespace App\Place\Infrastructure\ApiPlatform\Payload;

use App\Field\Infrastructure\ApiPlatform\Payload\FieldPayload;

final class UpdatePlacePayload 
{
    /** @var FieldPayload[] $fields */
    public ?array $fields;

    public function __construct(
        public string $id,
        ?array $fields,
    ) {
        $this->id = $id;
        $this->fields = $fields;
    }
}