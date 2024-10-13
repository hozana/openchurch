<?php

declare(strict_types=1);

namespace App\Filter\Field;

use ApiPlatform\Metadata\FilterInterface;
use Symfony\Component\PropertyInfo\Type;

final readonly class FieldTypeFilter implements FilterInterface
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