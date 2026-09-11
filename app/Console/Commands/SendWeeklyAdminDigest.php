<?php

namespace App\Console\Commands;

use App\Helpers\SettingHelper;
use App\Models\AiForgeRegistration;
use App\Models\Application;
use App\Models\CommunityMember;
use App\Models\EventRegistration;
use App\Notifications\Admin\WeeklyActivityDigestNotification;
use Illuminate\Console\Command;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Collection;

class SendWeeklyAdminDigest extends Command
{
    protected $signature = 'admin:send-weekly-digest';

    protected $description = 'Email admin one weekly roundup of community joins, applications, and event/AI Forge registrations instead of an email per signup';

    public function handle(): int
    {
        $periodEnd = now();
        $periodStart = $periodEnd->copy()->subWeek();

        $communityJoins = $this->summarize(
            CommunityMember::query()
                ->where('source', CommunityMember::SOURCE_JOIN_FORM)
                ->whereBetween('joined_at', [$periodStart, $periodEnd])
                ->with('tracks:id,name')
                ->get(),
            fn (CommunityMember $member) => $member->tracks->pluck('name'),
        );

        $applications = $this->summarize(
            Application::query()
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->with('program:id,name')
                ->get(),
            fn (Application $application) => [$application->program->name ?? 'Unknown program'],
        );

        $eventRegistrations = $this->summarize(
            EventRegistration::query()
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->with('event:id,title')
                ->get(),
            fn (EventRegistration $registration) => [$registration->event->title ?? 'Unknown event'],
        );

        $aiForgeRegistrations = $this->summarize(
            AiForgeRegistration::query()
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->with('event:id,title')
                ->get(),
            fn (AiForgeRegistration $registration) => [$registration->event->title ?? 'Unknown event'],
        );

        $total = $communityJoins['count'] + $applications['count']
            + $eventRegistrations['count'] + $aiForgeRegistrations['count'];

        if ($total === 0) {
            $this->info('No new signups this week — skipping digest.');

            return self::SUCCESS;
        }

        (new AnonymousNotifiable)
            ->route('mail', SettingHelper::contactEmail() ?? config('mail.from.address'))
            ->notify(new WeeklyActivityDigestNotification(
                periodStart: $periodStart,
                periodEnd: $periodEnd,
                communityJoins: $communityJoins,
                applications: $applications,
                eventRegistrations: $eventRegistrations,
                aiForgeRegistrations: $aiForgeRegistrations,
            ));

        $this->info("Weekly admin digest sent ({$total} signup(s)).");

        return self::SUCCESS;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Collection<int, TModel>  $records
     * @param  \Closure(TModel): iterable<string>  $groupLabels
     * @return array{count: int, breakdown: array<string, int>}
     */
    private function summarize(Collection $records, \Closure $groupLabels): array
    {
        return [
            'count' => $records->count(),
            'breakdown' => $records
                ->flatMap($groupLabels)
                ->countBy()
                ->sortDesc()
                ->all(),
        ];
    }
}
