<?php

namespace App\Services;

use App\Models\Cohort;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Internship;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Single home for "who is the audience" across the three attachable targets —
 * Course (enrolled students), Cohort and Program (interns). Assignments,
 * Schedules and Broadcast Notifications all resolve, authorize, and list their
 * audiences through here, so the course→internship/training generalization
 * lives in one place instead of being copied per controller.
 */
class LearningAudienceService
{
    /** Front-end attachable keys → model classes. */
    public const TYPES = [
        'course' => Course::class,
        'cohort' => Cohort::class,
        'program' => Program::class,
    ];

    public function __construct(private readonly RosterResolver $rosterResolver) {}

    /**
     * Resolve a validated attachable_type/attachable_id pair to its model.
     */
    public function resolveAttachable(string $type, int $id): Model
    {
        $modelClass = self::TYPES[$type] ?? abort(422, 'Unknown audience type.');

        return $modelClass::query()->findOrFail($id);
    }

    /** The front-end key ('course'|'cohort'|'program') for a model instance. */
    public function typeKey(?Model $attachable): ?string
    {
        if ($attachable === null) {
            return null;
        }

        return array_search(get_class($attachable), self::TYPES, true) ?: null;
    }

    /**
     * The student/intern user IDs belonging to an attachable.
     */
    public function studentIds(Model $attachable)
    {
        return $this->rosterResolver->resolveStudentIds($attachable)->unique()->values();
    }

    /**
     * Whether a tutor/supervisor may create work for this attachable: the
     * course's instructor, or the supervisor of the cohort's/program's pivot.
     */
    public function userCanManage(Model $attachable, int $userId): bool
    {
        return match (true) {
            $attachable instanceof Course => (int) $attachable->instructor_id === $userId,
            $attachable instanceof Cohort => $attachable->programs()->wherePivot('supervisor_id', $userId)->exists(),
            $attachable instanceof Program => $attachable->cohorts()->wherePivot('supervisor_id', $userId)->exists(),
            default => false,
        };
    }

    public function cohortsSupervisedBy(int $userId): Builder
    {
        return Cohort::query()->whereHas('programs', fn ($q) => $q->where('cohort_program.supervisor_id', $userId));
    }

    public function programsSupervisedBy(int $userId): Builder
    {
        return Program::query()->whereHas('cohorts', fn ($q) => $q->where('cohort_program.supervisor_id', $userId));
    }

    /**
     * The three audience groups a user may target: courses they instruct plus
     * cohorts/programs they supervise. This is the exact shape the manager UIs
     * (assignments, schedules, notifications) consume.
     *
     * @return array{courses:array<int,array<string,mixed>>,cohorts:array<int,array<string,mixed>>,programs:array<int,array<string,mixed>>}
     */
    public function managedGroupsFor(User $user): array
    {
        return [
            'courses' => $this->coursesWithRoster(Course::query()->where('instructor_id', $user->id)),
            'cohorts' => $this->cohortsWithRoster($this->cohortsSupervisedBy((int) $user->id)),
            'programs' => $this->programsWithRoster($this->programsSupervisedBy((int) $user->id)),
        ];
    }

    /**
     * Every audience group in the system — for admin-level targeting.
     *
     * @return array{courses:array<int,array<string,mixed>>,cohorts:array<int,array<string,mixed>>,programs:array<int,array<string,mixed>>}
     */
    public function allGroups(): array
    {
        return [
            'courses' => $this->coursesWithRoster(Course::query()),
            'cohorts' => $this->cohortsWithRoster(Cohort::query()),
            'programs' => $this->programsWithRoster(Program::query()),
        ];
    }

    /**
     * @return array<int, array{id:int,title:string,student_count:int,students:array<int,array{id:int,name:string,email:string}>}>
     */
    public function coursesWithRoster(Builder $query): array
    {
        return $query
            ->select('id', 'title')
            ->with(['enrollments:id,course_id,user_id,access_status', 'enrollments.user:id,name,email,role'])
            ->orderBy('title')
            ->get()
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'title' => $course->title,
                'student_count' => $course->enrollments->where('access_status', '!=', 'revoked')->count(),
                'students' => $course->enrollments
                    ->filter(fn (Enrollment $e) => $e->access_status !== 'revoked')
                    ->map(fn (Enrollment $e) => $e->user)
                    ->filter(fn ($u) => $u && $u->role === 'user')
                    ->unique('id')
                    ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id:int,title:string,student_count:int,students:array<int,array{id:int,name:string,email:string,program_id:int|null}>,programs:array<int,array{id:int,title:string}>}>
     */
    public function cohortsWithRoster(Builder $query): array
    {
        return $query
            ->select('id', 'name')
            ->with('programs:id,title')
            ->orderBy('name')
            ->get()
            ->map(function (Cohort $cohort) {
                $roster = $this->attachableRoster($cohort, $cohort->name);

                // Cohorts span multiple programs — attach each student's program
                // so the UI can offer an optional "narrow to this program" filter
                // without changing what the task/schedule is actually attached to.
                $programByUser = Internship::query()
                    ->where('cohort_id', $cohort->id)
                    ->pluck('program_id', 'user_id');

                $roster['students'] = collect($roster['students'])
                    ->map(fn (array $s) => [...$s, 'program_id' => $programByUser->get($s['id'])])
                    ->all();
                $roster['programs'] = $cohort->programs->map(fn (Program $p) => ['id' => $p->id, 'title' => $p->title])->values()->all();

                return $roster;
            })
            ->all();
    }

    /**
     * @return array<int, array{id:int,title:string,student_count:int,students:array<int,array{id:int,name:string,email:string}>}>
     */
    public function programsWithRoster(Builder $query): array
    {
        return $query
            ->select('id', 'title')
            ->orderBy('title')
            ->get()
            ->map(fn (Program $program) => $this->attachableRoster($program, $program->title))
            ->all();
    }

    /**
     * @return array{id:int,title:string,student_count:int,students:array<int,array{id:int,name:string,email:string}>}
     */
    public function attachableRoster(Model $attachable, string $title): array
    {
        $studentIds = $this->studentIds($attachable);

        $students = User::query()
            ->whereIn('id', $studentIds)
            ->get(['id', 'name', 'email'])
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
            ->all();

        return [
            'id' => $attachable->id,
            'title' => $title,
            'student_count' => count($students),
            'students' => $students,
        ];
    }
}
