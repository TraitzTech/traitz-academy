<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Models\InterviewQuestion;
use App\Models\InterviewResponse;
use App\Models\Program;
use App\Notifications\BatchEmailNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InterviewController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Interview::with(['program', 'creator'])
            ->withCount(['questions', 'responses']);

        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        $interviews = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/Interviews/Index', [
            'interviews' => $interviews,
            'filters' => $request->only(['search', 'program_id']),
            'programs' => Program::where('is_active', true)->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Interviews/Create', [
            'programs' => Program::where('is_active', true)->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'program_id' => 'nullable|exists:programs,id',
            'passing_score' => 'required|integer|min:1|max:100',
            'time_limit_minutes' => 'nullable|integer|min:1|max:480',
            'is_active' => 'boolean',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string|max:1000',
            'questions.*.type' => 'required|in:multiple_choice,text,boolean',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*' => 'string|max:500',
            'questions.*.correct_answer' => 'nullable|string|max:500',
            'questions.*.points' => 'required|integer|min:1|max:100',
        ]);

        $interview = Interview::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'program_id' => $validated['program_id'] ?? null,
            'created_by' => auth()->id(),
            'passing_score' => $validated['passing_score'],
            'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        foreach ($validated['questions'] as $index => $questionData) {
            $interview->questions()->create([
                'question' => $questionData['question'],
                'type' => $questionData['type'],
                'options' => $questionData['options'] ?? null,
                'correct_answer' => $questionData['correct_answer'] ?? null,
                'points' => $questionData['points'],
                'sort_order' => $index,
            ]);
        }

        return redirect()->route('admin.interviews.index')
            ->with('success', 'Interview created successfully with '.count($validated['questions']).' questions.');
    }

    public function edit(Interview $interview): Response
    {
        $interview->load('questions');

        return Inertia::render('Admin/Interviews/Edit', [
            'interview' => $interview,
            'programs' => Program::where('is_active', true)->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function update(Request $request, Interview $interview): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'program_id' => 'nullable|exists:programs,id',
            'passing_score' => 'required|integer|min:1|max:100',
            'time_limit_minutes' => 'nullable|integer|min:1|max:480',
            'is_active' => 'boolean',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|integer',
            'questions.*.question' => 'required|string|max:1000',
            'questions.*.type' => 'required|in:multiple_choice,text,boolean',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*' => 'string|max:500',
            'questions.*.correct_answer' => 'nullable|string|max:500',
            'questions.*.points' => 'required|integer|min:1|max:100',
        ]);

        $interview->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'program_id' => $validated['program_id'] ?? null,
            'passing_score' => $validated['passing_score'],
            'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Sync questions: keep existing by ID, create new, remove deleted
        $incomingIds = collect($validated['questions'])->pluck('id')->filter()->toArray();
        $interview->questions()->whereNotIn('id', $incomingIds)->delete();

        foreach ($validated['questions'] as $index => $questionData) {
            if (! empty($questionData['id'])) {
                InterviewQuestion::where('id', $questionData['id'])
                    ->where('interview_id', $interview->id)
                    ->update([
                        'question' => $questionData['question'],
                        'type' => $questionData['type'],
                        'options' => is_array($questionData['options'] ?? null) ? json_encode($questionData['options']) : null,
                        'correct_answer' => $questionData['correct_answer'] ?? null,
                        'points' => $questionData['points'],
                        'sort_order' => $index,
                    ]);
            } else {
                $interview->questions()->create([
                    'question' => $questionData['question'],
                    'type' => $questionData['type'],
                    'options' => $questionData['options'] ?? null,
                    'correct_answer' => $questionData['correct_answer'] ?? null,
                    'points' => $questionData['points'],
                    'sort_order' => $index,
                ]);
            }
        }

        return redirect()->route('admin.interviews.index')
            ->with('success', 'Interview updated successfully.');
    }

    public function destroy(Interview $interview): RedirectResponse
    {
        $interview->delete();

        return redirect()->route('admin.interviews.index')
            ->with('success', 'Interview deleted successfully.');
    }

    public function toggleStatus(Interview $interview): RedirectResponse
    {
        $interview->update(['is_active' => ! $interview->is_active]);

        return back()->with('success', 'Interview status updated.');
    }

    public function responses(Interview $interview): Response
    {
        $interview->load(['program', 'questions']);

        $responses = $interview->responses()
            ->with(['user', 'application.program'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/Interviews/Responses', [
            'interview' => $interview,
            'responses' => $responses,
        ]);
    }

    public function showResponse(Interview $interview, \App\Models\InterviewResponse $response): Response
    {
        $response->load(['user', 'application.program', 'answers.question', 'interview']);

        return Inertia::render('Admin/Interviews/ResponseDetail', [
            'interview' => $interview,
            'response' => $response,
        ]);
    }

    public function reviewResponse(Request $request, Interview $interview, InterviewResponse $response): RedirectResponse
    {
        if (! $response->requires_manual_review) {
            return back()->with('error', 'This response does not require manual review.');
        }

        if ($response->reviewed_at) {
            return back()->with('error', 'This response has already been reviewed.');
        }

        $validated = $request->validate([
            'scores' => 'required|array',
            'scores.*' => 'required|integer|min:0',
        ]);

        $response->load('answers.question');

        $totalEarned = 0;

        foreach ($response->answers as $answer) {
            $question = $answer->question;

            if ($question->type === 'text') {
                $scoreKey = (string) $answer->id;
                $awardedPoints = min($validated['scores'][$scoreKey] ?? 0, $question->points);

                $answer->update([
                    'points_earned' => $awardedPoints,
                    'is_correct' => $awardedPoints > 0,
                ]);

                $totalEarned += $awardedPoints;
            } else {
                $totalEarned += $answer->points_earned;
            }
        }

        $percentage = $response->total_points > 0
            ? round(($totalEarned / $response->total_points) * 100, 2)
            : 0;

        $passed = $percentage >= $interview->passing_score;

        $response->update([
            'score' => $totalEarned,
            'percentage' => $percentage,
            'passed' => $passed,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        // Auto-reject the linked application if the candidate failed
        if (! $passed && $response->application_id) {
            $failedApplication = $response->application()->with('program')->first();

            if ($failedApplication && $failedApplication->status !== 'rejected') {
                $failedApplication->update([
                    'status' => 'rejected',
                    'notes' => "Auto-rejected: interview score {$percentage}% is below the passing score of {$interview->passing_score}%.",
                    'reviewed_at' => now(),
                ]);

                if ($failedApplication->user) {
                    $failedApplication->user->notify(new BatchEmailNotification(
                        subject: 'Update on Your Application',
                        messageHtml: "<p>Thank you for your interest in {$failedApplication->program->title} and for completing the interview.</p><p>After reviewing your responses, we regret to inform you that your score did not meet the minimum requirement. We are unable to proceed with your application at this time.</p><p>We encourage you to keep developing your skills and apply again in the future. Feel free to explore other programs and opportunities on our platform.</p>",
                        actionText: 'Explore Programs',
                        actionUrl: url('/programs')
                    ));
                }
            }
        }

        return back()->with('success', "Response reviewed successfully. Score: {$totalEarned}/{$response->total_points} ({$percentage}%) — ".($passed ? 'Passed' : 'Not Passed'));
    }
}
