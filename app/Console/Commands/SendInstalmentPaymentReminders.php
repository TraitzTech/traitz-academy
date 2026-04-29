<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\CoursePayment;
use App\Models\Enrollment;
use App\Notifications\Lms\InstalmentPaymentDueReminderNotification;
use Illuminate\Console\Command;

class SendInstalmentPaymentReminders extends Command
{
    protected $signature = 'lms:send-instalment-reminders';

    protected $description = 'Send email and in-app reminders three days before scheduled instalment due dates';

    public function handle(): int
    {
        $targetDate = now()->addDays(3)->toDateString();

        $query = Enrollment::query()
            ->with('user')
            ->where('payment_type', 'instalment')
            ->where('access_status', 'active')
            ->whereDate('instalment_next_due_at', $targetDate)
            ->where(function ($q) {
                $q->whereNull('last_instalment_reminder_sent_at')
                    ->orWhereDate('last_instalment_reminder_sent_at', '<', now()->toDateString());
            });

        $count = 0;

        $query->chunkById(50, function ($enrollments) use (&$count) {
            foreach ($enrollments as $enrollment) {
                $course = Course::query()->find($enrollment->course_id);
                if (! $course) {
                    continue;
                }

                $user = $enrollment->user;
                if (! $user) {
                    continue;
                }

                $paidAmount = (float) CoursePayment::query()
                    ->where('user_id', $enrollment->user_id)
                    ->where('course_id', $enrollment->course_id)
                    ->where('status', 'successful')
                    ->sum('base_amount');

                $coursePrice = $course->effectivePrice();
                $remaining = max(0, round($coursePrice - $paidAmount, 2));

                if ($remaining <= 0.01) {
                    continue;
                }

                $maxInstallments = max(1, (int) ($course->max_installments ?? 1));
                $installmentAmount = $maxInstallments > 0
                    ? round($coursePrice / $maxInstallments, 2)
                    : $remaining;

                $amountDue = min($installmentAmount, $remaining);
                $currency = (string) config('services.mesomb.currency', 'XAF');
                $dueDate = $enrollment->instalment_next_due_at ?? now()->addDays(3);

                $enrollment->update(['last_instalment_reminder_sent_at' => now()]);
                $user->notify(new InstalmentPaymentDueReminderNotification(
                    $course,
                    (float) $amountDue,
                    $currency,
                    $dueDate
                ));
                $count++;
            }
        });

        $this->info("Queued {$count} instalment reminder(s).");

        return self::SUCCESS;
    }
}
