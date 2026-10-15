<?php

declare(strict_types=1);

namespace App\Admin\Application;

final readonly class ParishSearchPage
{
    /**
     * @param list<ParishRow> $rows
     * @param int             $total    number of matching parishes
     * @param int             $lastPage the last page that can be browsed, which may not reach all the matching parishes
     */
    public function __construct(
        public array $rows,
        public int $total,
        public int $page,
        public int $lastPage,
    ) {
    }
}
