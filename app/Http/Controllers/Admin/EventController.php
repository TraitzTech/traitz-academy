<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'event_date' => 'sometimes|required|date',
            'location' => 'sometimes|required|string|max:255',
            'is_online' => 'boolean',
            'event_url' => 'nullable|url|required_if:is_online,true',
            'capacity' => 'sometimes|required|integer|min:1',
            'category' => 'sometimes|required|string|in:webinar,workshop,networking,info-session,hackathon',
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
        $event->update(['is_active' => !$event->is_active]);

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
}
