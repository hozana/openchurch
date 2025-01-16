<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Infrastructure\ApiPlatform\Input;

use App\Field\Domain\Model\Field;

final class CommunityWikidataInput
{
    /**
     * @var array<Field[]>
     */
    public array $wikidataEntities = [];
}
