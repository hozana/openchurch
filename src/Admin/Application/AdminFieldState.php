<?php

declare(strict_types=1);

namespace App\Admin\Application;

use App\Field\Domain\FieldValueNormalizer;
use App\Field\Domain\Model\Field;

/**
 * The value an admin gave to a field and its explanation.
 */
final readonly class AdminFieldState
{
    /**
     * @param string|int|float|list<string>|null $value normalized value (see FieldValueNormalizer)
     */
    public function __construct(
        public string|int|float|array|null $value,
        public ?string $explanation,
    ) {
    }

    public static function of(?Field $adminField): self
    {
        return new self(FieldValueNormalizer::normalize($adminField?->getValue()), $adminField?->explanation);
    }

    public function equals(self $other): bool
    {
        return self::sameValue($this->value, $other->value) && $this->explanation === $other->explanation;
    }

    /**
     * The order of related communities doesn't matter.
     *
     * @param string|int|float|list<string>|null $a
     * @param string|int|float|list<string>|null $b
     */
    private static function sameValue(string|int|float|array|null $a, string|int|float|array|null $b): bool
    {
        if (is_array($a) && is_array($b)) {
            sort($a);
            sort($b);
        }

        return $a === $b;
    }
}
