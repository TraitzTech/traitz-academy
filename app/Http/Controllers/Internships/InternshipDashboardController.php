<?php

namespace App\Http\Controllers\Internships;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Internships\Concerns\ResolvesActiveInternship;
use App\Models\InternshipAttendance;
use App\Models\LogbookEntry;
use App\Support\Internships\OfficeGeofence;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InternshipDashboardController extends Controller
{
    use ResolvesActiveInternship;

    public function index(Request $request, OfficeGeofence $geofence): Response
    {
        $internship = $this->activeInternshipFor($request->user());
        $internship->load('program:id,title,office_days', 'cohort.programs:id,title', 'supervisor:id,name');
        $today = $this->todayFor($internship);
        $todayDate = \Carbon\Carbon::parse($today, $internship->timezone());

        $attendance = InternshipAttendance::query()
            ->where('internship_id', $internship->id)
            ->whereDate('date', $today)
            ->first();

        $logbook = LogbookEntry::query()
            ->where('internship_id', $internship->id)
            ->whereDate('date', $today)
            ->first();

        $supervisorId = $internship->effectiveSupervisorId();
        $supervisorName = $internship->supervisor?->name
            ?? ($supervisorId ? \App\Models\User::whereKey($supervisorId)->value('name') : null);

        return Inertia::render('Internships/Dashboard', [
            'internship' => [
                'id' => $internship->id,
                'program' => $internship->program?->title,
                'cohort' => $internship->cohort?->name,
                'supervisor' => $supervisorName,
                'start_date' => optional($internship->start_date)->toDateString(),
                'end_date' => optional($internship->end_date)->toDateString(),
                'status' => $internship->status,
                'work_mode' => $internship->work_mode,
            ],
            'today' => $today,
            'attendance' => $attendance ? [
                'clock_in_at' => optional($attendance->clock_in_at)->toIso8601String(),
                'clock_out_at' => optional($attendance->clock_out_at)->toIso8601String(),
                'hours' => $attendance->hours,
                'status' => $attendance->status,
            ] : null,
            'logbook' => $logbook ? [
                'content' => $logbook->content,
                'hours_spent' => $logbook->hours_spent,
                'learnings' => $logbook->learnings,
                'blockers' => $logbook->blockers,
                'status' => $logbook->status,
                'supervisor_feedback' => $logbook->supervisor_feedback,
            ] : null,
            'requiresLocation' => $geofence->isEnforced(),
            'requiresLogbookBeforeClockOut' => (bool) config('internship.require_logbook_before_clock_out', true),
            'todayIsOfficeDay' => empty($internship->program?->office_days) ? null : $internship->program->isOfficeDay($todayDate),
            'officeDaysLabel' => $internship->program?->officeDaysLabel(),
        ]);
    }
}
