<?php

namespace App\Support\Lms;

use App\Models\User;

/**
 * Keys stored in users.notification_preferences (JSON).
 * A key set to false means the learner opted out of that category.
 * Mandatory keys cannot be disabled and are ignored if present in JSON.
 */
final class LmsNotificationPreference
{
    public const ENROLMENT_CONFIRMATION = 'enrolment_confirmation';

    public const PAYMENT_RECEIVED = 'payment_received';

    public const PAYMENT_FAILED = 'payment_failed';

    public const ACCESS_SUSPENDED = 'access_suspended';

    public const CERTIFICATE_DELIVERY = 'certificate_delivery';

    public const INSTALMENT_REMINDER = 'instalment_reminder';

    public const COURSE_COMPLETION = 'course_completion';

    public const QUIZ_GRADED = 'quiz_graded';

    public const NEW_COURSE_PUBLISHED = 'new_course_published';

    public const INSTRUCTOR_LESSON_QUESTIONS = 'instructor_lesson_questions';

    public const SCHEDULE_UPDATES = 'schedule_updates';

    /**
     * Categories that must always be delivered (email + in-app where applicable).
     *
     * @var list<string>
     */
    public const MANDATORY = [
        self::ENROLMENT_CONFIRMATION,
        self::PAYMENT_RECEIVED,
        self::PAYMENT_FAILED,
        self::ACCESS_SUSPENDED,
        self::CERTIFICATE_DELIVERY,
    ];

    /**
     * Human-readable labels for optional toggles in account settings.
     *
     * @return array<string, string>
     */
    public static function optionalLabels(): array
    {
        return [
            self::INSTALMENT_REMINDER => 'Upcoming instalment reminders',
            self::COURSE_COMPLETION => 'Course completion (certificate generating)',
            self::QUIZ_GRADED => 'Quiz graded (short answer feedback)',
            self::NEW_COURSE_PUBLISHED => 'New courses published on the platform',
            self::INSTRUCTOR_LESSON_QUESTIONS => 'Lesson questions from your students (instructors)',
            self::SCHEDULE_UPDATES => 'Schedule updates and reminders',
        ];
    }

    public static function accepts(User $user, string $key): bool
    {
        if (in_array($key, self::MANDATORY, true)) {
            return true;
        }

        $prefs = $user->notification_preferences;
        if (! is_array($prefs)) {
            return true;
        }

        if (! array_key_exists($key, $prefs)) {
            return true;
        }

        return (bool) $prefs[$key];
    }
}
