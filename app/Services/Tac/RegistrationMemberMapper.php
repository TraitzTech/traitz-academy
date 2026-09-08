<?php

namespace App\Services\Tac;

use App\Models\AiForgeRegistration;
use App\Models\Application;
use App\Models\CommunityMember;
use App\Models\Enrollment;
use App\Models\EventRegistration;
use App\Models\Internship;
use Illuminate\Database\Eloquent\Model;

/**
 * Translates any Traitz Academy registration record into the shape
 * {@see CommunityEnrollmentService::record()} expects.
 *
 * Adding a new "front door" to the academy means adding a case here and
 * observing the model — nothing else changes.
 */
class RegistrationMemberMapper
{
    /**
     * Models whose creation should auto-include the person in TAC.
     *
     * @var array<int, class-string<Model>>
     */
    public const OBSERVED_MODELS = [
        Application::class,
        EventRegistration::class,
        AiForgeRegistration::class,
        Enrollment::class,
        Internship::class,
    ];

    /**
     * @return array{email: string, attributes: array<string, mixed>, source: string, context: string|null}|null
     */
    public function map(Model $model): ?array
    {
        return match (true) {
            $model instanceof Application => $this->fromApplication($model),
            $model instanceof EventRegistration => $this->fromEventRegistration($model),
            $model instanceof AiForgeRegistration => $this->fromAiForgeRegistration($model),
            $model instanceof Enrollment => $this->fromEnrollment($model),
            $model instanceof Internship => $this->fromInternship($model),
            default => null,
        };
    }

    protected function fromApplication(Application $application): ?array
    {
        if (blank($application->email)) {
            return null;
        }

        return [
            'email' => $application->email,
            'attributes' => [
                'first_name' => $application->first_name,
                'last_name' => $application->last_name,
                'phone' => $application->phone,
                'school' => $application->institution_name,
                'current_status' => CommunityMember::STATUS_STUDENT,
                'user_id' => $application->user_id,
            ],
            'source' => CommunityMember::SOURCE_PROGRAM_APPLICATION,
            'context' => $application->program?->title,
        ];
    }

    protected function fromEventRegistration(EventRegistration $registration): ?array
    {
        if (blank($registration->email)) {
            return null;
        }

        return [
            'email' => $registration->email,
            'attributes' => [
                'first_name' => $registration->first_name,
                'last_name' => $registration->last_name,
                'phone' => $registration->phone,
                'user_id' => $registration->user_id,
            ],
            'source' => CommunityMember::SOURCE_EVENT,
            'context' => $registration->event?->title,
        ];
    }

    protected function fromAiForgeRegistration(AiForgeRegistration $registration): ?array
    {
        if (blank($registration->email)) {
            return null;
        }

        return [
            'email' => $registration->email,
            'attributes' => [
                'first_name' => $registration->first_name,
                'last_name' => $registration->last_name,
                'phone' => $registration->phone,
                'school' => $registration->organization,
                'user_id' => $registration->user_id,
            ],
            'source' => CommunityMember::SOURCE_AI_FORGE,
            'context' => $registration->event?->title ?? 'AI Forge',
        ];
    }

    protected function fromEnrollment(Enrollment $enrollment): ?array
    {
        $user = $enrollment->user;

        if (! $user || blank($user->email)) {
            return null;
        }

        return [
            'email' => $user->email,
            'attributes' => [
                ...CommunityEnrollmentService::splitName($user->name),
                'phone' => $user->phone,
                'user_id' => $user->id,
            ],
            'source' => CommunityMember::SOURCE_COURSE,
            'context' => $enrollment->course?->title,
        ];
    }

    protected function fromInternship(Internship $internship): ?array
    {
        $user = $internship->intern;

        if (! $user || blank($user->email)) {
            return null;
        }

        return [
            'email' => $user->email,
            'attributes' => [
                ...CommunityEnrollmentService::splitName($user->name),
                'phone' => $user->phone,
                'user_id' => $user->id,
            ],
            'source' => CommunityMember::SOURCE_INTERNSHIP,
            'context' => $internship->program?->title,
        ];
    }
}
