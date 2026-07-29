<?php

namespace App\Services;

use App\Models\OfficeLocation;

class AttendanceService
{
    public const INSIDE_RADIUS  = 'INSIDE_RADIUS';

    public const OUTSIDE_RADIUS = 'OUTSIDE_RADIUS';

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
     * Classify a GPS reading against the office radius by distance alone.
     * GPS accuracy is stored for display/audit but never factors into this
     * decision — it cannot block or reclassify a check-in/check-out.
     */
    public function classifyLocation(float $distance, float $accuracy, OfficeLocation $office): string
    {
        return $distance <= (float) $office->radius_meters
            ? self::INSIDE_RADIUS
            : self::OUTSIDE_RADIUS;
    }

    public function getActiveOffice(): ?OfficeLocation
    {
        return OfficeLocation::where('is_active', true)->first();
    }
}
