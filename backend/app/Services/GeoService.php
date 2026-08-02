<?php

declare(strict_types=1);

namespace App\Services;

class GeoService
{
    private const EARTH_RADIUS_METRES = 6371000;

    public function distanceInMetres(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude
    ): float {
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($fromLatitude)) * cos(deg2rad($toLatitude)) * sin($longitudeDelta / 2) ** 2;

        return self::EARTH_RADIUS_METRES * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function isWithinRadius(
        float $latitude,
        float $longitude,
        float $centreLatitude,
        float $centreLongitude,
        int $radiusInMetres
    ): bool {
        return $this->distanceInMetres($latitude, $longitude, $centreLatitude, $centreLongitude)
            <= $radiusInMetres;
    }
}
