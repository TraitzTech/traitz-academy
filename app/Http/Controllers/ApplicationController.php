<?php

namespace App\Http\Controllers;

use App\Helpers\SettingHelper;
use App\Http\Requests\StoreApplicationRequest;
use App\Models\Application;
use App\Models\Program;
use App\Notifications\ApplicationConfirmation;
use App\Notifications\NewApplicationSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\AnonymousNotifiable;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    public function create(Program $program): \Inertia\Response
    {
        return Inertia::render('Applications/Create', [
            'program' => $program,
        ]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();
        $validated['application_type'] = $request->input('application_type', 'professional');

        // Handle internship letter upload
        if ($request->hasFile('internship_letter')) {
            $validated['internship_letter_path'] = $request->file('internship_letter')
                ->store('internship-letters', 'public');
        }
        unset($validated['internship_letter']);

        $application = Application::create($validated);

        // Send notification email to admin
        $adminEmail = SettingHelper::contactEmail() ?? config('mail.from.address');
        $notifiable = new AnonymousNotifiable;
        $notifiable->route('mail', $adminEmail)
            ->notify(new NewApplicationSubmitted($application));

        // Send confirmation email to applicant
        $applicantNotifiable = new AnonymousNotifiable;
        $applicantNotifiable->route('mail', $application->email)
            ->notify(new ApplicationConfirmation($application));

        return redirect('/dashboard')->with('success', 'Thank you for your application! We\'ve sent you a confirmation email. You can view its status in your dashboard.');
    }
}
