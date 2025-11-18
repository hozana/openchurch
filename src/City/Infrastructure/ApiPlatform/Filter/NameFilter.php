<?php

declare(strict_types=1);

namespace App\City\Infrastructure\ApiPlatform\Filter;

use ApiPlatform\Metadata\FilterInterface;
use Symfony\Component\PropertyInfo\Type;

final class NameFilter implements FilterInterface
{
    public function getDescription(string $resourceClass): array
    {
        return [
            'name' => [
                'property' => 'name',
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
            ],
        ];
    }
}
