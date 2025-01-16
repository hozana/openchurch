<?php

declare(strict_types=1);

namespace App\FieldHolder\Place\Infrastructure\ApiPlatform\Input;

final class PlaceWikidataInput {

    /**
     * @param array<Field[]> $fields
     */
    public array $wikidataEntities = [];
}