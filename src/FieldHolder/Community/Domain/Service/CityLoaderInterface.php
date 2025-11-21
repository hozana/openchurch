<?php

declare(strict_types=1);

namespace App\FieldHolder\Community\Domain\Service;

interface CityLoaderInterface
{
    /**
     * @return array<array{name: string, zipCode: string, inseeCode: string}>
     */
    public function getCities(?string $citiesDownloadUrl): array;
}
