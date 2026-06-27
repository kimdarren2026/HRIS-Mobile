<?php

namespace App\Services;

use App\Models\OfficeLocation;

class AttendanceService
{
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

    public function getActiveOffice(): ?OfficeLocation
    {
        return OfficeLocation::where('is_active', true)->first();
    }
}
