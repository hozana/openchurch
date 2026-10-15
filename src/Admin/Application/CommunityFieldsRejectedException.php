<?php

declare(strict_types=1);

namespace App\Admin\Application;

use RuntimeException;

final class CommunityFieldsRejectedException extends RuntimeException
{
    /**
     * @param list<string> $errors
     */
    public function __construct(
        public readonly array $errors,
    ) {
        parent::__construct(implode(' ', $errors));
    }
}
