<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Infrastructure\ApiPlatform\Input;

final class CommunityWikidataInput {

    /**
     * @param array<Field[]> $fields
     */
    public array $wikidataEntities = [];
}