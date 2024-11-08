<?php

declare(strict_types=1);

namespace App\Field\Infrastructure\ApiPlatform\Filter;

use ApiPlatform\Metadata\FilterInterface;
use Symfony\Component\PropertyInfo\Type;

final class FieldWikidataIdFilter implements FilterInterface
{
    public function getDescription(string $resourceClass): array
    {
        return [
            'messeInfoId' => [
                'property' => 'wikidataId',
                'type' => Type::BUILTIN_TYPE_INT,
                'required' => false,
            ],
        ];
    }
}