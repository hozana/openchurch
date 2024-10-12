<?php

namespace App\Model\Request;

class FieldMutation
{
    public string $name;
    public mixed $value;
    public string $source;
    public string $reliability;
    public ?string $explanation;
}
