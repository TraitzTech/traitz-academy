<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $currentSessionId = $request->session()->getId();

        $sessions = DB::table('sessions')
            ->select('id', 'ip_address', 'user_agent', 'last_activity')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function (object $session) use ($currentSessionId): array {
                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'device' => $this->deviceName((string) ($session->user_agent ?? 'Unknown Device')),
                    'last_active' => now()->setTimestamp((int) $session->last_activity)->diffForHumans(),
                    'is_current' => $session->id === $currentSessionId,
                ];
            })
            ->values();

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'sessions' => $sessions,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function deviceName(string $userAgent): string
    {
        $agent = str($userAgent)->lower();

        $platform = match (true) {
            $agent->contains('iphone') => 'iPhone',
            $agent->contains('ipad') => 'iPad',
            $agent->contains('android') => 'Android',
            $agent->contains('windows') => 'Windows',
            $agent->contains('mac os') || $agent->contains('macintosh') => 'Mac',
            $agent->contains('linux') => 'Linux',
            default => 'Unknown OS',
        };

        $browser = match (true) {
            $agent->contains('edg') => 'Edge',
            $agent->contains('chrome') => 'Chrome',
            $agent->contains('safari') && ! $agent->contains('chrome') => 'Safari',
            $agent->contains('firefox') => 'Firefox',
            default => 'Browser',
        };

        return "{$platform} • {$browser}";
    }
}
