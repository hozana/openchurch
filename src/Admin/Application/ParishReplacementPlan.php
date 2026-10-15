<?php

declare(strict_types=1);

namespace App\Admin\Application;

/**
 * What an edit changes about the parishes a parish replaces.
 */
final readonly class ParishReplacementPlan
{
    /**
     * @param list<string> $designated ids of the parishes the parish newly replaces
     * @param list<string> $released   ids of the parishes the parish no longer replaces
     */
    public function __construct(
        public array $designated,
        public array $released,
    ) {
    }
}
