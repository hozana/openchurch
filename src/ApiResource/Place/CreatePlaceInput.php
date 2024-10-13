<?php

declare(strict_types=1);

namespace App\ApiResource\Place;

final readonly class CreatePlaceInput 
{
    /** @var CreatePlaceInputField[] $fields */
    public ?array $fields;

    public function __construct(
        ?array $fields
    ) {
        $this->fields = $fields;
    }
}