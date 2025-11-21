<?php

declare(strict_types=1);

namespace App\Field\Infrastructure\ApiPlatform\Filter;

use ApiPlatform\Metadata\FilterInterface;
use App\Field\Domain\Enum\FieldCommunity;
use Symfony\Component\TypeInfo\Type;

final class FieldParentCommunityIdFilter implements FilterInterface
{
    public function getDescription(string $resourceClass): array
    {
        return [
            'parentCommunityId' => [
                'property' => FieldCommunity::PARENT_COMMUNITY_ID->value,
                'type' => Type::string()->__toString(),
                'required' => false,
            ],
        ];
    }
}
