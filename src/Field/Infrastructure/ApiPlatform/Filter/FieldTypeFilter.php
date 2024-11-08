<?php

declare(strict_types=1);

namespace App\Field\Infrastructure\ApiPlatform\Filter;

use ApiPlatform\Metadata\FilterInterface;
use Symfony\Component\PropertyInfo\Type;

final class FieldTypeFilter implements FilterInterface
{
    public function getDescription(string $resourceClass): array
    {
        return [
            'type' => [
                'property' => 'type',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
            ],
        ];
    }
}