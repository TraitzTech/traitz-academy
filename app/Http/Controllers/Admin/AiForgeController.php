<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiForgeEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AiForgeController extends Controller
{
    public function index(): Response
    {
        $event = AiForgeEvent::query()
            ->withCount(['registrations', 'orders', 'swags'])
            ->first();

        return Inertia::render('Admin/AiForge/Settings', [
            'event' => $event,
            'registrationCount' => $event?->registrations_count ?? 0,
            'orderCount' => $event?->orders_count ?? 0,
            'totalRevenue' => $event ? $event->orders()->where('payment_status', 'paid')->sum('total_amount') : 0,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateEvent($request);

        $validated['slug'] = Str::slug($validated['title']);

        $this->handleImageUploads($request, $validated);
        $this->handleJsonFields($request, $validated);

        unset($validated['hero_image_file'], $validated['logo_image_file']);

        AiForgeEvent::create($validated);

        return redirect()->route('admin.ai-forge.index')->with('success', 'AI Forge event created successfully.');
    }

    public function update(Request $request, AiForgeEvent $event): RedirectResponse
    {
        $validated = $this->validateEvent($request);

        $validated['slug'] = Str::slug($validated['title']);

        $this->handleImageUploads($request, $validated, $event);
        $this->handleJsonFields($request, $validated);

        unset($validated['hero_image_file'], $validated['logo_image_file']);

        $event->update($validated);

        return redirect()->route('admin.ai-forge.index')->with('success', 'AI Forge event updated successfully.');
    }

    public function toggleActive(AiForgeEvent $event): RedirectResponse
    {
        $event->update(['is_active' => ! $event->is_active]);

        return back()->with('success', 'AI Forge visibility updated.');
    }

    public function toggleRegistration(AiForgeEvent $event): RedirectResponse
    {
        $event->update(['registration_open' => ! $event->registration_open]);

        return back()->with('success', 'Registration status updated.');
    }

    public function toggleSwagStore(AiForgeEvent $event): RedirectResponse
    {
        $event->update(['swag_store_active' => ! $event->swag_store_active]);

        return back()->with('success', 'Swag store status updated.');
    }

    public function updateStats(Request $request, AiForgeEvent $event): RedirectResponse
    {
        $validated = $request->validate([
            'stats' => ['required', 'array'],
        ]);

        $event->update(['stats' => $validated['stats']]);

        return back()->with('success', 'Stats updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEvent(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_online' => ['boolean'],
            'event_url' => ['nullable', 'url'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'hero_image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'logo_image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'is_active' => ['boolean'],
            'registration_open' => ['boolean'],
            'registration_fee' => ['nullable', 'integer', 'min:0'],
            'early_bird_fee' => ['nullable', 'integer', 'min:0'],
            'early_bird_deadline' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'max:10'],
            'swag_store_active' => ['boolean'],
            'registration_note' => ['nullable', 'string', 'max:1000'],
            'benefits' => ['nullable', 'string'],
            'schedule' => ['nullable', 'string'],
            'sponsors' => ['nullable', 'string'],
            'faqs' => ['nullable', 'string'],
        ]);
    }

    private function handleImageUploads(Request $request, array &$validated, ?AiForgeEvent $event = null): void
    {
        if ($request->hasFile('hero_image_file')) {
            if ($event?->hero_image && Storage::disk('public')->exists($event->hero_image)) {
                Storage::disk('public')->delete($event->hero_image);
            }
            $validated['hero_image'] = $request->file('hero_image_file')->store('ai-forge', 'public');
        }

        if ($request->hasFile('logo_image_file')) {
            if ($event?->logo_image && Storage::disk('public')->exists($event->logo_image)) {
                Storage::disk('public')->delete($event->logo_image);
            }
            $validated['logo_image'] = $request->file('logo_image_file')->store('ai-forge', 'public');
        }
    }

    private function handleJsonFields(Request $request, array &$validated): void
    {
        foreach (['benefits', 'schedule', 'sponsors', 'faqs'] as $field) {
            if (isset($validated[$field]) && is_string($validated[$field])) {
                $decoded = json_decode($validated[$field], true);
                $validated[$field] = is_array($decoded) ? $decoded : null;
            }
        }
    }
}
