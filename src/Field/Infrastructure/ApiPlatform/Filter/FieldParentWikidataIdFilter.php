<?php

declare(strict_types=1);

namespace App\Field\Infrastructure\ApiPlatform\Filter;

use ApiPlatform\Metadata\FilterInterface;
use App\Field\Domain\Enum\FieldCommunity;
use Symfony\Component\PropertyInfo\Type;

final class FieldParentWikidataIdFilter implements FilterInterface
{
    public function getDescription(string $resourceClass): array
    {
        return [
            'parentWikidataId' => [
                'property' => FieldCommunity::PARENT_WIKIDATA_ID->value,
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
            ],
        ];
    }
}
