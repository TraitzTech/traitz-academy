<?php

namespace App\Support\Internships;

use App\Models\SiteSetting;

/**
 * Server-side office geofence for intern clock-in. Distance is computed with
 * the haversine formula from the office coordinates — the client only supplies
 * its coordinates, never a "within office" verdict.
 *
 * Coordinates and radius come from admin Site Settings (set via the settings
 * page), falling back to config/env. A tolerance buffer is added to the radius
 * so ordinary GPS drift never falsely rejects an intern.
 */
class OfficeGeofence
{
    private const EARTH_RADIUS_METERS = 6_371_000;

    public function isEnforced(): bool
    {
        return (bool) config('internship.office.enforce_location', true)
            && $this->isConfigured();
    }

    public function isConfigured(): bool
    {
        return $this->officeLatitude() !== null && $this->officeLongitude() !== null;
    }

    public function radiusMeters(): int
    {
        $setting = SiteSetting::get('office_radius_meters');
        if ($setting !== null && $setting !== '') {
            return (int) $setting;
        }

        return (int) config('internship.office.radius_meters', 200);
    }

    /**
     * Safety buffer added to the radius to absorb normal GPS inaccuracy, so an
     * intern standing at the office isn't rejected by a few metres of drift.
     */
    public function toleranceMeters(): int
    {
        return (int) config('internship.office.tolerance_meters', 100);
    }

    /**
     * The effective allowed distance: configured radius + GPS tolerance buffer.
     */
    public function allowedDistanceMeters(): int
    {
        return $this->radiusMeters() + $this->toleranceMeters();
    }

    /**
     * Great-circle distance in metres between the given point and the office.
     */
    public function distanceMeters(float $latitude, float $longitude): float
    {
        $lat1 = deg2rad((float) $this->officeLatitude());
        $lng1 = deg2rad((float) $this->officeLongitude());
        $lat2 = deg2rad($latitude);
        $lng2 = deg2rad($longitude);

        $dLat = $lat2 - $lat1;
        $dLng = $lng2 - $lng1;

        $a = sin($dLat / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($dLng / 2) ** 2;

        return self::EARTH_RADIUS_METERS * 2 * asin(min(1.0, sqrt($a)));
    }

    /**
     * Whether the point is within the office radius. When enforcement is off or
     * the office is unconfigured, every location is accepted.
     */
    public function isWithinOffice(float $latitude, float $longitude): bool
    {
        if (! $this->isEnforced()) {
            return true;
        }

        return $this->distanceMeters($latitude, $longitude) <= $this->allowedDistanceMeters();
    }

    private function officeLatitude(): ?float
    {
        return $this->coordinate('office_latitude', 'internship.office.latitude');
    }

    private function officeLongitude(): ?float
    {
        return $this->coordinate('office_longitude', 'internship.office.longitude');
    }

    private function coordinate(string $settingKey, string $configKey): ?float
    {
        $value = SiteSetting::get($settingKey);
        if ($value === null || $value === '') {
            $value = config($configKey);
        }

        return $value === null || $value === '' ? null : (float) $value;
    }
}
