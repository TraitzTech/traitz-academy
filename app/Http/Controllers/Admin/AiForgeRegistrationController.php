<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiForgeEvent;
use App\Models\AiForgeRegistration;
use App\Notifications\AiForgeEventReminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Inertia\Inertia;
use Inertia\Response;

class AiForgeRegistrationController extends Controller
{
    public function index(Request $request): Response
    {
        $event = AiForgeEvent::query()->first();

        $query = AiForgeRegistration::query()->with('user');

        if ($event) {
            $query->where('ai_forge_event_id', $event->id);
        }

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
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => AiForgeRegistration::query()->where('ai_forge_event_id', $event?->id)->count(),
            'confirmed' => AiForgeRegistration::query()->where('ai_forge_event_id', $event?->id)->where('status', 'confirmed')->count(),
            'registered' => AiForgeRegistration::query()->where('ai_forge_event_id', $event?->id)->where('status', 'registered')->count(),
            'cancelled' => AiForgeRegistration::query()->where('ai_forge_event_id', $event?->id)->where('status', 'cancelled')->count(),
        ];

        return Inertia::render('Admin/AiForge/Registrations/Index', [
            'registrations' => $registrations,
            'filters' => $request->only(['search', 'status']),
            'stats' => $stats,
            'event' => $event ? ['id' => $event->id, 'title' => $event->title] : null,
        ]);
    }

    public function updateStatus(Request $request, AiForgeRegistration $registration): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:registered,confirmed,cancelled,attended'],
        ]);

        $registration->update([
            'status' => $validated['status'],
            'confirmed_at' => $validated['status'] === 'confirmed' ? now() : $registration->confirmed_at,
        ]);

        return back()->with('success', 'Registration status updated.');
    }

    public function destroy(AiForgeRegistration $registration): RedirectResponse
    {
        $registration->delete();

        return back()->with('success', 'Registration deleted.');
    }

    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:ai_forge_registrations,id'],
            'status' => ['required', 'string', 'in:registered,confirmed,cancelled,attended'],
        ]);

        AiForgeRegistration::whereIn('id', $validated['ids'])->update([
            'status' => $validated['status'],
        ]);

        $count = count($validated['ids']);

        return back()->with('success', "{$count} registration(s) updated.");
    }

    public function sendReminder(Request $request, AiForgeRegistration $registration): RedirectResponse
    {
        $notifiable = new AnonymousNotifiable;
        $notifiable->route('mail', $registration->email)
            ->notify(new AiForgeEventReminder($registration));

        return back()->with('success', "Reminder sent to {$registration->full_name}.");
    }

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $event = AiForgeEvent::query()->first();

        $registrations = AiForgeRegistration::query()
            ->where('ai_forge_event_id', $event?->id)
            ->with('user')
            ->latest()
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="ai-forge-registrations.csv"',
        ];

        return response()->streamDownload(function () use ($registrations) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['First Name', 'Last Name', 'Email', 'Phone', 'Country', 'Organization', 'Status', 'Registered At']);

            foreach ($registrations as $registration) {
                fputcsv($handle, [
                    $registration->first_name,
                    $registration->last_name,
                    $registration->email,
                    $registration->phone,
                    $registration->country,
                    $registration->organization,
                    $registration->status,
                    $registration->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        }, 'ai-forge-registrations.csv', $headers);
    }
}
