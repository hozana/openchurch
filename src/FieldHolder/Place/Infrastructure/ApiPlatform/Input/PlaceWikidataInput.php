<?php

declare(strict_types=1);

namespace App\FieldHolder\Place\Infrastructure\ApiPlatform\Input;

use App\Field\Domain\Model\Field;

final class PlaceWikidataInput
{
    /**
     * @var array<Field[]>
     */
    public array $wikidataEntities = [];
}
