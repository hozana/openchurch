<?php

declare(strict_types=1);

namespace App\Core\Domain\Search\Service;

interface CityLoaderInterface
{
    /**
     * @return array<{cityName: string, postCode: int}>
     */
    public function getCities(?string $citiesDownloadUrl): array;
}
