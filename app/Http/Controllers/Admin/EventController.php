<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Notifications\EventReminderNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Event::query();

        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $events = $query->withCount('registrations')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Events/Index', [
            'events' => $events,
            'filters' => $request->only(['search', 'category', 'status']),
            'categories' => [
                'webinar' => 'Webinar',
                'workshop' => 'Workshop',
                'networking' => 'Networking',
                'info-session' => 'Info Session',
                'hackathon' => 'Hackathon',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Events/Create', [
            'categories' => [
                'webinar' => 'Webinar',
                'workshop' => 'Workshop',
                'networking' => 'Networking',
                'info-session' => 'Info Session',
                'hackathon' => 'Hackathon',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'event_date' => 'required|date|after:now',
            'location' => 'required|string|max:255',
            'is_online' => 'boolean',
            'event_url' => 'nullable|url|required_if:is_online,true',
            'capacity' => 'required|integer|min:1',
            'category' => 'required|string|in:webinar,workshop,networking,info-session,hackathon',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_active' => 'boolean',
            'agenda' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $validated['registered_count'] = 0;

        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')->store('events', 'public');
        }

        unset($validated['image']);

        Event::create($validated);

        return redirect()->route('admin.events.index')
            ->with('success', 'Event created successfully.');
    }

    public function edit(Event $event): Response
    {
        return Inertia::render('Admin/Events/Edit', [
            'event' => $event,
            'categories' => [
                'webinar' => 'Webinar',
                'workshop' => 'Workshop',
                'networking' => 'Networking',
                'info-session' => 'Info Session',
                'hackathon' => 'Hackathon',
            ],
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'event_date' => 'required|date',
            'location' => 'required|string|max:255',
            'is_online' => 'boolean',
            'event_url' => 'nullable|url|required_if:is_online,true',
            'capacity' => 'required|integer|min:1',
            'category' => 'required|string|in:webinar,workshop,networking,info-session,hackathon',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_active' => 'boolean',
            'agenda' => 'nullable|string',
        ]);

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($event->image_url && Storage::disk('public')->exists($event->image_url)) {
                Storage::disk('public')->delete($event->image_url);
            }
            $validated['image_url'] = $request->file('image')->store('events', 'public');
        }

        unset($validated['image']);

        $event->update($validated);

        return redirect()->route('admin.events.index')
            ->with('success', 'Event updated successfully.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        // Delete image if exists
        if ($event->image_url && Storage::disk('public')->exists($event->image_url)) {
            Storage::disk('public')->delete($event->image_url);
        }

        $event->delete();

        return redirect()->route('admin.events.index')
            ->with('success', 'Event deleted successfully.');
    }

    public function toggleStatus(Event $event): RedirectResponse
    {
        $event->update(['is_active' => ! $event->is_active]);

        return back()->with('success', 'Event status updated.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:events,id',
        ]);

        $events = Event::whereIn('id', $validated['ids'])->get();

        foreach ($events as $event) {
            if ($event->image_url && Storage::disk('public')->exists($event->image_url)) {
                Storage::disk('public')->delete($event->image_url);
            }
            $event->delete();
        }

        $count = count($validated['ids']);

        return back()->with('success', "{$count} event(s) deleted successfully.");
    }

    public function registrations(Request $request, Event $event): Response
    {
        $query = $event->registrations()->with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $registrations = $query->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Events/Registrations', [
            'event' => $event->loadCount('registrations'),
            'registrations' => $registrations,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function updateRegistrationStatus(Request $request, Event $event, EventRegistration $registration): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:registered,confirmed,cancelled,attended',
        ]);

        $registration->update([
            'status' => $validated['status'],
            'attended_at' => $validated['status'] === 'attended' ? now() : $registration->attended_at,
        ]);

        return back()->with('success', 'Registration status updated.');
    }

    public function bulkUpdateRegistrationStatus(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:event_registrations,id',
            'status' => 'required|string|in:registered,confirmed,cancelled,attended',
        ]);

        $registrations = EventRegistration::whereIn('id', $validated['ids'])
            ->where('event_id', $event->id)
            ->get();

        foreach ($registrations as $registration) {
            $registration->update([
                'status' => $validated['status'],
                'attended_at' => $validated['status'] === 'attended' ? now() : $registration->attended_at,
            ]);
        }

        $count = $registrations->count();

        return back()->with('success', "{$count} registration(s) updated to {$validated['status']}.");
    }

    public function bulkDeleteRegistrations(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:event_registrations,id',
        ]);

        $count = EventRegistration::whereIn('id', $validated['ids'])
            ->where('event_id', $event->id)
            ->delete();

        return back()->with('success', "{$count} registration(s) deleted successfully.");
    }

    public function sendReminder(Event $event): RedirectResponse
    {
        $registrations = $event->registrations()->get();

        if ($registrations->isEmpty()) {
            return back()->with('error', 'No registrations found for this event.');
        }

        foreach ($registrations as $registration) {
            $notifiable = new AnonymousNotifiable;
            $notifiable->route('mail', $registration->email)
                ->notify(new EventReminderNotification($registration));
        }

        return back()->with('success', "Reminder sent to {$registrations->count()} registrant(s).");
    }
}
