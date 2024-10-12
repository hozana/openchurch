<?php

declare(strict_types=1);

namespace App\ApiResource\Community;
use App\ApiResource\Community\CreateCommunityInputField;

final readonly class CreateCommunityInput 
{
    /** @var CreateCommunityInputField[] $fields */
    public ?array $fields;

    public function __construct(
        ?array $fields
    ) {
        $this->fields = $fields;
    }
}