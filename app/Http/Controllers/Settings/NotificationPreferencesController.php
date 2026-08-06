<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\Lms\LmsNotificationPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationPreferencesController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $stored = is_array($user->notification_preferences) ? $user->notification_preferences : [];

        $optional = [];
        foreach (LmsNotificationPreference::optionalLabels() as $key => $label) {
            $optional[] = [
                'key' => $key,
                'label' => $label,
                'enabled' => ! array_key_exists($key, $stored) ? true : (bool) $stored[$key],
            ];
        }

        return Inertia::render('settings/Notifications', [
            'optionalPreferences' => $optional,
            'mandatoryNotice' => 'Enrolment confirmations, payment notifications, and certificate delivery cannot be disabled.',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $allowed = array_keys(LmsNotificationPreference::optionalLabels());

        $request->validate([
            'preferences' => ['required', 'array'],
        ]);

        $clean = [];
        foreach ($allowed as $key) {
            $clean[$key] = $request->boolean('preferences.'.$key, true);
        }

        $request->user()->update([
            'notification_preferences' => $clean,
        ]);

        return to_route('notifications.edit')->with('success', 'Notification preferences saved.');
    }
}
