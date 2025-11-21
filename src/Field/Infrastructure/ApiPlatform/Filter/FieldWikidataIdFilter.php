<?php

declare(strict_types=1);

namespace App\Field\Infrastructure\ApiPlatform\Filter;

use ApiPlatform\Metadata\FilterInterface;
use App\Field\Domain\Enum\FieldCommunity;
use Symfony\Component\TypeInfo\Type;

final class FieldWikidataIdFilter implements FilterInterface
{
    public function getDescription(string $resourceClass): array
    {
        return [
            'messeInfoId' => [
                'property' => FieldCommunity::WIKIDATA_ID->value,
                'type' => Type::int(),
                'required' => false,
            ],
        ];
    }
}
