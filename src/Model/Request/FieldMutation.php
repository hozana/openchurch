<?php

namespace App\Model\Request;

class FieldMutation
{
    public string $name;
    public mixed $value;
    public string $engine;
    public string $reliability;
    public ?string $source;
    public ?string $explanation;
}
