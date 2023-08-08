<?php
declare(strict_types=1);

namespace App\Services;


interface GeoLocationApiInterface
{
    public function getLocationInfo(string $address): array;
}
