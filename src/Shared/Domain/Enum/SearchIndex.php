<?php

declare(strict_types=1);

namespace App\Shared\Domain\Enum;

enum SearchIndex: string
{
    case PARISH = 'parish';

    case DIOCESE = 'diocese';

    public function getName(): string
    {
        return 'enum_search_index';
    }
}
