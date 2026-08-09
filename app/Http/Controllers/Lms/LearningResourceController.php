<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\LearningResource;
use App\Models\Program;
use App\Services\LearningAudienceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Program-scoped resources a supervisor shares with their interns — never
 * public. Mirrors AssignmentController's tutor/student split, but the
 * attachable is always a Program (supervisors don't own courses/cohorts here).
 */
class LearningResourceController extends Controller
{
    public function __construct(private readonly LearningAudienceService $audience) {}

    public function tutorIndex(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->canManageLearningOps(), 403);

        return Inertia::render('Tutor/Resources/Index', [
            'programs' => $this->audience->supervisedProgramsWithRoster((int) $user->id),
            'resources' => $this->resourceRowsForTutor((int) $user->id),
        ]);
    }

    public function tutorStore(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->canManageLearningOps(), 403);

        $validated = $this->validatePayload($request);

        $program = Program::query()->findOrFail((int) $validated['program_id']);
        abort_unless($this->audience->userCanManage($program, (int) $user->id), 403);

        $allowedStudentIds = $this->audience->supervisedProgramStudentIds((int) $user->id, $program);

        $audience = (string) $validated['audience'];
        $selectedIds = collect($validated['student_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->values();

        if ($audience === 'selected_students') {
            $selectedIds = $selectedIds->filter(fn ($id) => $allowedStudentIds->contains($id))->values();

            if ($selectedIds->isEmpty()) {
                throw ValidationException::withMessages(['student_ids' => 'Please select at least one valid intern.']);
            }
        } else {
            $selectedIds = collect();
        }

        $this->assertContentPresent($validated);

        $payload = [
            'title' => $validated['title'],
            'slug' => $this->generateUniqueSlug((string) $validated['title']),
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'youtube_url' => $validated['type'] === 'youtube_video' ? ($validated['youtube_url'] ?? null) : null,
            'external_url' => $validated['type'] === 'external_link' ? ($validated['external_url'] ?? null) : null,
            'content' => $validated['type'] === 'writing' ? ($validated['content'] ?? null) : null,
            'is_active' => true,
            'published_at' => now(),
            'attachable_type' => Program::class,
            'attachable_id' => $program->id,
            'created_by' => $user->id,
            'audience' => $audience,
        ];

        if ($validated['type'] === 'document' && $request->hasFile('document')) {
            $payload['document_path'] = $request->file('document')->store('resources', 'public');
        }

        $resource = LearningResource::create($payload);
        $resource->selectedStudents()->sync($selectedIds->all());

        return back()->with('success', "Resource \"{$resource->title}\" shared.");
    }

    public function tutorDestroy(Request $request, LearningResource $learningResource): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->canManageLearningOps(), 403);
        abort_unless((int) $learningResource->created_by === (int) $user->id, 403);

        if ($learningResource->document_path && Storage::disk('public')->exists($learningResource->document_path)) {
            Storage::disk('public')->delete($learningResource->document_path);
        }

        $learningResource->delete();

        return back()->with('success', 'Resource removed.');
    }

    public function studentIndex(Request $request): Response
    {
        $userId = (int) $request->user()->id;

        $programIds = Internship::query()->where('user_id', $userId)->pluck('program_id')->filter()->unique();

        $resources = LearningResource::query()
            ->where('attachable_type', Program::class)
            ->whereIn('attachable_id', $programIds)
            ->active()
            ->where(function ($query) use ($userId) {
                $query->where('audience', 'all_program_interns')
                    ->orWhereHas('selectedStudents', fn ($q) => $q->where('users.id', $userId));
            })
            ->with(['attachable:id,title', 'creator:id,name'])
            ->latest()
            ->get()
            ->map(fn (LearningResource $resource) => $this->mapResourceRow($resource));

        return Inertia::render('Lms/Resources/Index', [
            'resources' => $resources,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:document,youtube_video,writing,external_link'],
            'description' => ['nullable', 'string', 'max:3000'],
            'document' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,txt', 'max:10240'],
            'youtube_url' => ['nullable', 'url', 'max:500'],
            'external_url' => ['nullable', 'url', 'max:500'],
            'content' => ['nullable', 'string'],
            'audience' => ['required', 'in:all_program_interns,selected_students'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:users,id'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function assertContentPresent(array $validated): void
    {
        if ($validated['type'] === 'document' && ! request()->hasFile('document')) {
            throw ValidationException::withMessages(['document' => 'A document file is required for document resources.']);
        }

        if ($validated['type'] === 'youtube_video' && empty($validated['youtube_url'])) {
            throw ValidationException::withMessages(['youtube_url' => 'A YouTube link is required for video resources.']);
        }

        if ($validated['type'] === 'external_link' && empty($validated['external_url'])) {
            throw ValidationException::withMessages(['external_url' => 'An external URL is required for link resources.']);
        }

        if ($validated['type'] === 'writing' && empty($validated['content'])) {
            throw ValidationException::withMessages(['content' => 'Writing content is required for writing resources.']);
        }
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    private function resourceRowsForTutor(int $tutorId): array
    {
        return LearningResource::query()
            ->where('created_by', $tutorId)
            ->with(['attachable:id,title', 'creator:id,name', 'selectedStudents:id'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (LearningResource $resource) => $this->mapResourceRow($resource))
            ->all();
    }

    /**
     * @return array<string,mixed>
     */
    private function mapResourceRow(LearningResource $resource): array
    {
        return [
            'id' => $resource->id,
            'title' => $resource->title,
            'type' => $resource->type,
            'description' => $resource->description,
            'youtube_url' => $resource->youtube_url,
            'external_url' => $resource->external_url,
            'content' => $resource->content,
            'document_url' => $resource->document_path ? asset('storage/'.$resource->document_path) : null,
            'audience' => $resource->audience,
            'program' => $resource->attachable ? ['id' => $resource->attachable->id, 'title' => $resource->attachable->title] : null,
            'created_by' => $resource->creator?->name,
            'selected_students_count' => $resource->audience === 'selected_students' ? $resource->selectedStudents->count() : null,
            'created_at' => $resource->created_at?->toIso8601String(),
        ];
    }

    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (LearningResource::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
