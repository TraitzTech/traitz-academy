<?php

namespace App\Http\Controllers;

use App\Helpers\SettingHelper;
use App\Http\Requests\RegisterEventRequest;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Notifications\EventRegistrationConfirmation;
use App\Notifications\NewEventRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\AnonymousNotifiable;
use Inertia\Inertia;

class EventController extends Controller
{
    public function index(): \Inertia\Response
    {
        $events = Event::where('is_active', true)
            ->orderBy('event_date')
            ->get();

        $userRegisteredEventIds = [];
        if (auth()->check()) {
            $userRegisteredEventIds = EventRegistration::where('user_id', auth()->id())
                ->pluck('event_id')
                ->toArray();
        }

        return Inertia::render('Events/Index', [
            'events' => $events,
            'userRegisteredEventIds' => $userRegisteredEventIds,
        ]);
    }

    public function show(Event $event): \Inertia\Response
    {
        $isRegistered = false;
        if (auth()->check()) {
            $isRegistered = EventRegistration::where('user_id', auth()->id())
                ->where('event_id', $event->id)
                ->exists();
        }

        return Inertia::render('Events/Show', [
            'event' => $event,
            'isRegistered' => $isRegistered,
        ]);
    }

    public function register(RegisterEventRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();
        $registration = EventRegistration::create($validated);

        // Send notification email to admin
        $adminEmail = SettingHelper::contactEmail() ?? config('mail.from.address');
        $notifiable = new AnonymousNotifiable;
        $notifiable->route('mail', $adminEmail)
            ->notify(new NewEventRegistration($registration));

        // Send confirmation email to registrant
        $registrant = new AnonymousNotifiable;
        $registrant->route('mail', $registration->email)
            ->notify(new EventRegistrationConfirmation($registration));

        return redirect()->route('events.show', $registration->event->slug)
            ->with('success', 'Thanks for registering! We\'ve emailed you the event details.');
    }
}
