<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Domain\Service;

interface CityLoaderInterface
{
    /**
     * @return array<{cityName: string, postCode: int}>
     */
    public function getCities(?string $citiesDownloadUrl): array;
}
