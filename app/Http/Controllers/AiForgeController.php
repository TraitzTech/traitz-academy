<?php

namespace App\Http\Controllers;

use App\Helpers\SettingHelper;
use App\Models\AiForgeEvent;
use App\Models\AiForgeRegistration;
use App\Models\AiForgeSwag;
use App\Notifications\AiForgeRegistrationConfirmation;
use App\Notifications\NewAiForgeRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Inertia\Inertia;
use Inertia\Response;

class AiForgeController extends Controller
{
    public function index(): Response|RedirectResponse
    {
        $event = AiForgeEvent::query()->where('is_active', true)->first();

        if (! $event) {
            return redirect()->route('home')->with('info', 'AI Forge is not available at this time.');
        }

        $event->loadCount('registrations');

        $featuredSwags = AiForgeSwag::query()
            ->where('ai_forge_event_id', $event->id)
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->limit(4)
            ->get();

        $isRegistered = false;
        if (auth()->check()) {
            $isRegistered = AiForgeRegistration::query()
                ->where('ai_forge_event_id', $event->id)
                ->where('user_id', auth()->id())
                ->exists();
        }

        return Inertia::render('AiForge/Index', [
            'event' => $event,
            'featuredSwags' => $featuredSwags,
            'isRegistered' => $isRegistered,
            'currentFee' => $this->getCurrentFee($event),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $event = AiForgeEvent::query()
            ->where('is_active', true)
            ->where('registration_open', true)
            ->firstOrFail();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'organization' => ['nullable', 'string', 'max:255'],
            'motivation' => ['nullable', 'string', 'max:1000'],
        ]);

        $existingRegistration = AiForgeRegistration::query()
            ->where('ai_forge_event_id', $event->id)
            ->where('email', $validated['email'])
            ->exists();

        if ($existingRegistration) {
            return back()->with('error', 'This email is already registered for AI Forge.');
        }

        if ($event->capacity && $event->registrations()->count() >= $event->capacity) {
            return back()->with('error', 'Sorry, AI Forge has reached maximum capacity.');
        }

        $validated['ai_forge_event_id'] = $event->id;
        $validated['user_id'] = auth()->id();
        $validated['amount_paid'] = $this->getCurrentFee($event)['amount'];
        $validated['payment_status'] = $validated['amount_paid'] > 0 ? 'pending' : 'free';

        $registration = AiForgeRegistration::create($validated);

        // Update stats
        $stats = $event->stats ?? [];
        $stats['total_registered'] = $event->registrations()->count();
        $event->update(['stats' => $stats]);

        // Notify admin
        $adminEmail = SettingHelper::contactEmail() ?? config('mail.from.address');
        $notifiable = new AnonymousNotifiable;
        $notifiable->route('mail', $adminEmail)
            ->notify(new NewAiForgeRegistration($registration));

        // Notify registrant
        $registrant = new AnonymousNotifiable;
        $registrant->route('mail', $registration->email)
            ->notify(new AiForgeRegistrationConfirmation($registration));

        return back()->with('success', 'Welcome to AI Forge! Check your email for confirmation details.');
    }

    /**
     * Determine the current registration fee (early bird or regular).
     *
     * @return array{amount: int, is_early_bird: bool, label: string, currency: string}
     */
    private function getCurrentFee(AiForgeEvent $event): array
    {
        $currency = $event->currency ?? 'XAF';

        if ($event->registration_fee <= 0) {
            return ['amount' => 0, 'is_early_bird' => false, 'label' => 'Free', 'currency' => $currency];
        }

        $isEarlyBird = $event->early_bird_fee !== null
            && $event->early_bird_deadline !== null
            && now()->lte($event->early_bird_deadline);

        return [
            'amount' => $isEarlyBird ? $event->early_bird_fee : $event->registration_fee,
            'is_early_bird' => $isEarlyBird,
            'label' => $isEarlyBird ? 'Early Bird' : 'Regular',
            'currency' => $currency,
            'regular_fee' => $event->registration_fee,
            'early_bird_fee' => $event->early_bird_fee,
            'early_bird_deadline' => $event->early_bird_deadline?->toDateString(),
        ];
    }
}
