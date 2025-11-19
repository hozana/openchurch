<?php

declare(strict_types=1);

namespace App\Field\Infrastructure\ApiPlatform\Filter;

use ApiPlatform\Metadata\FilterInterface;
use Symfony\Component\PropertyInfo\Type;

final class FieldContactZipCodeFilter implements FilterInterface
{
    public function getDescription(string $resourceClass): array
    {
        return [
            'contactZipcodes' => [
                'property' => 'contactZipcodes',
                'type' => Type::BUILTIN_TYPE_ARRAY,
                'required' => false,
                'schema' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
        ];
    }
}
