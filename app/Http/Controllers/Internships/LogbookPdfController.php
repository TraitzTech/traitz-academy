<?php

namespace App\Http\Controllers\Internships;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Internships\Concerns\ResolvesActiveInternship;
use App\Models\Internship;
use App\Models\LogbookEntry;
use App\Models\SiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogbookPdfController extends Controller
{
    use ResolvesActiveInternship;

    /**
     * The signed-off logbook for the current intern's own active internship.
     */
    public function own(Request $request): Response
    {
        return $this->render($this->activeInternshipFor($request->user()));
    }

    /**
     * The logbook for a specific internship (supervisor/admin, policy-gated).
     */
    public function forInternship(Request $request, Internship $internship): Response
    {
        $this->authorize('view', $internship);

        return $this->render($internship);
    }

    private function render(Internship $internship): Response
    {
        $internship->load(
            'intern:id,name,email',
            'program:id,title',
            'cohort.programs:id,title',
            'supervisor:id,name',
        );

        $entries = LogbookEntry::query()
            ->where('internship_id', $internship->id)
            ->orderBy('date')
            ->get();

        $supervisorId = $internship->effectiveSupervisorId();
        $supervisor = $internship->supervisor
            ?? ($supervisorId ? \App\Models\User::query()->find($supervisorId) : null);
        $siteName = (string) (SiteSetting::get('site_name') ?? config('app.name'));

        $fileName = 'logbook-'.Str::slug($internship->intern?->name ?? 'intern').'.pdf';

        return Pdf::loadView('internships.logbook-pdf', [
            'internship' => $internship,
            'entries' => $entries,
            'supervisor' => $supervisor,
            'siteName' => $siteName,
        ])->download($fileName);
    }
}
