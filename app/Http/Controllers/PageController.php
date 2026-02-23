<?php

namespace App\Http\Controllers;

use App\Helpers\SettingHelper;
use App\Http\Requests\ContactFormRequest;
use App\Models\Application;
use App\Models\Event;
use App\Models\Program;
use App\Models\SuccessStory;
use App\Notifications\ContactFormConfirmation;
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

        $successStories = SuccessStory::active()
            ->ordered()
            ->limit(3)
            ->get();

        $careerOpenings = Program::where('is_active', true)
            ->whereIn('category', ['professional-internship', 'job-opportunity'])
            ->latest()
            ->limit(6)
            ->get();

        return Inertia::render('Home', [
            'stats' => $stats,
            'featuredPrograms' => $featuredPrograms,
            'upcomingEvents' => $upcomingEvents,
            'successStories' => $successStories,
            'careerOpenings' => $careerOpenings,
        ]);
    }

    public function successStories(): \Inertia\Response
    {
        $successStories = SuccessStory::active()
            ->ordered()
            ->get();

        return Inertia::render('SuccessStories/Index', [
            'successStories' => $successStories,
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

        // Send notification to the configured admin email
        $adminNotifiable = new AnonymousNotifiable;
        $adminNotifiable->route('mail', $recipientEmail)
            ->notify(new ContactFormSubmission(
                $validated['name'],
                $validated['email'],
                $validated['subject'],
                $validated['message'],
            ));

        // Send confirmation email to the sender
        $senderNotifiable = new AnonymousNotifiable;
        $senderNotifiable->route('mail', $validated['email'])
            ->notify(new ContactFormConfirmation(
                $validated['name'],
                $validated['subject'],
                $validated['message'],
            ));

        return back()->with('success', 'Your message has been sent successfully. We\'ll get back to you soon!');
    }
}
