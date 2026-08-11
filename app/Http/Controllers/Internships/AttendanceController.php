<?php

namespace App\Http\Controllers\Internships;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Internships\Concerns\ResolvesActiveInternship;
use App\Models\InternshipAttendance;
use App\Models\LogbookEntry;
use App\Support\Internships\OfficeGeofence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use ResolvesActiveInternship;

    /**
     * Clock in for today — only from within the office geofence.
     */
    public function clockIn(Request $request, OfficeGeofence $geofence): RedirectResponse
    {
        $internship = $this->activeInternshipFor($request->user());
        $today = $this->todayFor($internship);

        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $attendance = InternshipAttendance::query()
            ->where('internship_id', $internship->id)
            ->whereDate('date', $today)
            ->first();

        if ($attendance && $attendance->clock_in_at !== null) {
            return back()->with('warning', 'You have already clocked in today.');
        }

        $latitude = $validated['latitude'] ?? null;
        $longitude = $validated['longitude'] ?? null;
        $distance = null;

        if ($geofence->isEnforced()) {
            if ($latitude === null || $longitude === null) {
                return back()->withErrors([
                    'location' => 'Location is required to clock in. Please allow location access and try again.',
                ]);
            }

            $distance = (int) round($geofence->distanceMeters((float) $latitude, (float) $longitude));

            if ($distance > $geofence->allowedDistanceMeters()) {
                return back()->withErrors([
                    'location' => 'You must be at the office to clock in. You appear to be about '.$distance.'m away.',
                ]);
            }
        } elseif ($latitude !== null && $longitude !== null && $geofence->isConfigured()) {
            // Not enforced, but still record how far away they were for audit.
            $distance = (int) round($geofence->distanceMeters((float) $latitude, (float) $longitude));
        }

        $attendance ??= new InternshipAttendance([
            'internship_id' => $internship->id,
            'date' => $today,
        ]);
        $attendance->fill([
            'clock_in_at' => now(),
            'clock_in_latitude' => $latitude,
            'clock_in_longitude' => $longitude,
            'clock_in_distance_m' => $distance,
            'status' => InternshipAttendance::STATUS_PRESENT,
            'source' => InternshipAttendance::SOURCE_SELF,
        ]);
        $attendance->save();

        return back()->with('success', 'Clocked in. Have a great day!');
    }

    /**
     * Clock out for today — requires a completed logbook entry first.
     */
    public function clockOut(Request $request): RedirectResponse
    {
        $internship = $this->activeInternshipFor($request->user());
        $today = $this->todayFor($internship);

        $attendance = InternshipAttendance::query()
            ->where('internship_id', $internship->id)
            ->whereDate('date', $today)
            ->first();

        if (! $attendance || $attendance->clock_in_at === null) {
            return back()->with('warning', 'You have not clocked in today.');
        }

        if ($attendance->clock_out_at !== null) {
            return back()->with('warning', 'You have already clocked out today.');
        }

        if (config('internship.require_logbook_before_clock_out', true) && ! $this->logbookIsComplete($internship->id, $today)) {
            return back()->withErrors([
                'logbook' => 'Please complete and submit your logbook for today before clocking out.',
            ]);
        }

        $clockOut = now();
        $hours = round($attendance->clock_in_at->diffInMinutes($clockOut) / 60, 2);

        $attendance->update([
            'clock_out_at' => $clockOut,
            'hours' => $hours,
        ]);

        return back()->with('success', 'Clocked out. See you next time!');
    }

    private function logbookIsComplete(int $internshipId, string $date): bool
    {
        return LogbookEntry::query()
            ->where('internship_id', $internshipId)
            ->whereDate('date', $date)
            ->whereIn('status', [LogbookEntry::STATUS_SUBMITTED, LogbookEntry::STATUS_APPROVED])
            ->whereRaw("TRIM(content) <> ''")
            ->exists();
    }
}
