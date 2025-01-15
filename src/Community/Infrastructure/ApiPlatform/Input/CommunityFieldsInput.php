<?php

declare(strict_types=1);

namespace App\Community\Infrastructure\ApiPlatform\Input;

final class CommunityFieldsInput {

    /**
     * @param array<Field[]> $fields
     */
    public array $wikidataEntities = [];
}