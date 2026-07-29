<?php

namespace App\Services;

use App\Models\OfficeLocation;

class AttendanceService
{
    public const INSIDE_RADIUS      = 'INSIDE_RADIUS';

    public const OUTSIDE_RADIUS     = 'OUTSIDE_RADIUS';

    public const LOCATION_UNCERTAIN = 'LOCATION_UNCERTAIN';

    public function calculateDistance(float $lat, float $lng, OfficeLocation $office): float
    {
        $earthRadius = 6371000; // metres
        $dLat = deg2rad($lat - (float) $office->latitude);
        $dLng = deg2rad($lng - (float) $office->longitude);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad((float) $office->latitude)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function isWithinRadius(float $lat, float $lng, OfficeLocation $office): bool
    {
        return $this->calculateDistance($lat, $lng, $office) <= $office->radius_meters;
    }

    /**
     * Classify a GPS reading against the office radius, treating GPS accuracy
     * as an uncertainty margin rather than trusting the raw distance:
     *   - INSIDE_RADIUS   only if distance + accuracy <= radius (worst case still inside)
     *   - OUTSIDE_RADIUS  only if distance - accuracy >  radius (best case still outside)
     *   - LOCATION_UNCERTAIN otherwise (the radius boundary falls inside the error margin)
     */
    public function classifyLocation(float $distance, float $accuracy, OfficeLocation $office): string
    {
        $radius = (float) $office->radius_meters;

        if ($distance + $accuracy <= $radius) {
            return self::INSIDE_RADIUS;
        }

        if ($distance - $accuracy > $radius) {
            return self::OUTSIDE_RADIUS;
        }

        return self::LOCATION_UNCERTAIN;
    }

    public function getActiveOffice(): ?OfficeLocation
    {
        return OfficeLocation::where('is_active', true)->first();
    }
}
