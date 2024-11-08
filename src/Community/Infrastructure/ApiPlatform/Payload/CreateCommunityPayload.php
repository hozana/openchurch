<?php

declare(strict_types=1);

namespace App\Community\Infrastructure\ApiPlatform\Payload;

use App\Field\Infrastructure\ApiPlatform\Payload\FieldPayload;

final class CreateCommunityPayload 
{
    /** @var FieldPayload[] $fields */
    public ?array $fields;

    public function __construct(
        ?array $fields
    ) {
        $this->fields = $fields;
    }
}