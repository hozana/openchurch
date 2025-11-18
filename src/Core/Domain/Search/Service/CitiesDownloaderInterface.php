<?php

declare(strict_types=1);

namespace App\Core\Domain\Search\Service;

interface CitiesDownloaderInterface
{
    /**
     * @return array<{cityName: string, postCode: int}>
     */
    public function getCities(): array;
}
