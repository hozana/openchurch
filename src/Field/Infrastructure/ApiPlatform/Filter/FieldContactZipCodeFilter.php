<?php

declare(strict_types=1);

namespace App\Field\Infrastructure\ApiPlatform\Filter;

use ApiPlatform\Metadata\FilterInterface;
use Symfony\Component\TypeInfo\Type;

final class FieldContactZipCodeFilter implements FilterInterface
{
    public function getDescription(string $resourceClass): array
    {
        return [
            'contactZipcodes' => [
                'property' => 'contactZipcodes',
                'type' => Type::array(Type::string()),
                'required' => false,
                'schema' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
        ];
    }
}
