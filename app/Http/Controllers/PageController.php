<?php

namespace App\Http\Controllers;

use App\Helpers\SettingHelper;
use App\Http\Requests\ContactFormRequest;
use App\Models\Application;
use App\Models\Event;
use App\Models\Program;
use App\Models\User;
use App\Notifications\ContactFormSubmission;
use Illuminate\Notifications\AnonymousNotifiable;
use Inertia\Inertia;

class PageController extends Controller
{
    public function home(): \Inertia\Response
    {
        $stats = [
            'students_trained' => Application::count() + 300, // Base 300 + current applications
            'programs_count' => Program::where('is_active', true)->count(),
            'events_count' => Event::where('is_active', true)->count(),
        ];

        $featuredPrograms = Program::where('is_active', true)
            ->where('is_featured', true)
            ->limit(3)
            ->get();

        $upcomingEvents = Event::where('is_active', true)
            ->where('event_date', '>=', now())
            ->orderBy('event_date')
            ->limit(3)
            ->get();

        return Inertia::render('Home', [
            'stats' => $stats,
            'featuredPrograms' => $featuredPrograms,
            'upcomingEvents' => $upcomingEvents,
        ]);
    }

    public function about(): \Inertia\Response
    {
        return Inertia::render('About');
    }

    public function contact(): \Inertia\Response
    {
        return Inertia::render('Contact');
    }

    public function submitContact(ContactFormRequest $request)
    {
        $validated = $request->validated();

        // Get recipient email from database or environment variable
        $recipientEmail = SettingHelper::contactEmail() ?? config('mail.from.address');

        // Send notification to the configured email
        $notifiable = new AnonymousNotifiable;
        $notifiable->route('mail', $recipientEmail)
            ->notify(new ContactFormSubmission(
                $validated['name'],
                $validated['email'],
                $validated['subject'],
                $validated['message'],
            ));

        return back()->with('success', 'Your message has been sent successfully. We\'ll get back to you soon!');
    }
}
