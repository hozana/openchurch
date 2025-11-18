<?php

declare(strict_types=1);

namespace App\Field\Infrastructure\ApiPlatform\Filter;

use ApiPlatform\Metadata\FilterInterface;
use App\Field\Domain\Enum\FieldCommunity;
use Symfony\Component\PropertyInfo\Type;

final class FieldContactZipCodeFilter implements FilterInterface
{
    public function getDescription(string $resourceClass): array
    {
        return [
            'contactZipcode' => [
                'property' => FieldCommunity::CONTACT_ZIPCODE->value,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
            ],
        ];
    }
}
