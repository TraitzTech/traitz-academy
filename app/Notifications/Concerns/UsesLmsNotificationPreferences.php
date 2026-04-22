<?php

namespace App\Notifications\Concerns;

use App\Models\User;
use App\Support\Lms\LmsNotificationPreference;

trait UsesLmsNotificationPreferences
{
    /**
     * @return list<string>
     */
    protected function channelsFor(User $user, string $preferenceKey, bool $includeMail, bool $includeDatabase = true): array
    {
        if (! LmsNotificationPreference::accepts($user, $preferenceKey)) {
            return [];
        }

        $channels = [];
        if ($includeDatabase) {
            $channels[] = 'database';
        }
        if ($includeMail) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
