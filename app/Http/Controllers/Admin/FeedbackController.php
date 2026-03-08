<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFeedbackFormRequest;
use App\Models\FeedbackAnswer;
use App\Models\FeedbackForm;
use App\Models\FeedbackQuestion;
use App\Models\FeedbackResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackController extends Controller
{
    public function index(Request $request): Response
    {
        $query = FeedbackForm::with('creator')
            ->withCount('responses');

        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $forms = $query->latest()->paginate(12)->withQueryString();

        return Inertia::render('Admin/Feedback/Index', [
            'forms' => $forms,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Feedback/Create');
    }

    public function store(StoreFeedbackFormRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $form = FeedbackForm::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'created_by' => auth()->id(),
            'is_active' => $validated['is_active'] ?? true,
            'allow_anonymous' => $validated['allow_anonymous'] ?? true,
            'send_thank_you_email' => $validated['send_thank_you_email'] ?? true,
            'closes_at' => $validated['closes_at'] ?? null,
        ]);

        foreach ($validated['questions'] as $index => $questionData) {
            $form->questions()->create([
                'question' => $questionData['question'],
                'type' => $questionData['type'],
                'options' => $questionData['options'] ?? null,
                'required' => $questionData['required'] ?? true,
                'sort_order' => $index,
            ]);
        }

        return redirect()->route('admin.feedback.show', $form)
            ->with('success', 'Feedback form created successfully.');
    }

    public function show(FeedbackForm $feedback): Response
    {
        $feedback->load(['creator', 'questions']);

        $responses = $feedback->responses()
            ->with(['user', 'answers.question'])
            ->latest()
            ->paginate(20);

        $analytics = $feedback->questions->map(function (FeedbackQuestion $question) use ($feedback) {
            $answers = FeedbackAnswer::whereHas(
                'response',
                fn ($q) => $q->where('feedback_form_id', $feedback->id)
            )->where('feedback_question_id', $question->id)
                ->pluck('answer')
                ->filter()
                ->values();

            if ($question->type === 'multiple_choice' && $question->options) {
                $counts = collect($question->options)->mapWithKeys(
                    fn ($opt) => [$opt => $answers->filter(fn ($a) => $a === $opt)->count()]
                );
                $stats = [
                    'type' => 'chart',
                    'labels' => $counts->keys()->values(),
                    'data' => $counts->values()->values(),
                    'total' => $answers->count(),
                ];
            } else {
                $stats = [
                    'type' => 'text',
                    'responses' => $answers->take(50)->values(),
                    'total' => $answers->count(),
                ];
            }

            return [
                'question_id' => $question->id,
                'question' => $question->question,
                'type' => $question->type,
                'stats' => $stats,
            ];
        });

        return Inertia::render('Admin/Feedback/Show', [
            'form' => $feedback,
            'responses' => $responses,
            'analytics' => $analytics,
            'shareUrl' => route('feedback.fill', $feedback->slug),
            'stats' => [
                'total_responses' => $feedback->responses()->count(),
                'anonymous_responses' => $feedback->responses()->where('is_anonymous', true)->count(),
                'identified_responses' => $feedback->responses()->where('is_anonymous', false)->count(),
            ],
        ]);
    }

    public function edit(FeedbackForm $feedback): Response
    {
        $feedback->load('questions');

        return Inertia::render('Admin/Feedback/Edit', [
            'form' => $feedback,
        ]);
    }

    public function update(StoreFeedbackFormRequest $request, FeedbackForm $feedback): RedirectResponse
    {
        $validated = $request->validated();

        $feedback->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'allow_anonymous' => $validated['allow_anonymous'] ?? true,
            'send_thank_you_email' => $validated['send_thank_you_email'] ?? true,
            'closes_at' => $validated['closes_at'] ?? null,
        ]);

        $feedback->questions()->delete();
        foreach ($validated['questions'] as $index => $questionData) {
            $feedback->questions()->create([
                'question' => $questionData['question'],
                'type' => $questionData['type'],
                'options' => $questionData['options'] ?? null,
                'required' => $questionData['required'] ?? true,
                'sort_order' => $index,
            ]);
        }

        return redirect()->route('admin.feedback.show', $feedback)
            ->with('success', 'Feedback form updated successfully.');
    }

    public function destroy(FeedbackForm $feedback): RedirectResponse
    {
        $feedback->delete();

        return redirect()->route('admin.feedback.index')
            ->with('success', 'Feedback form deleted successfully.');
    }

    public function toggleStatus(FeedbackForm $feedback): RedirectResponse
    {
        $feedback->update(['is_active' => ! $feedback->is_active]);

        return back()->with('success', $feedback->is_active ? 'Form activated.' : 'Form deactivated.');
    }

    public function destroyResponse(FeedbackForm $feedback, FeedbackResponse $response): RedirectResponse
    {
        abort_unless($response->feedback_form_id === $feedback->id, 404);

        $response->answers()->delete();
        $response->delete();

        return back()->with('success', 'Response deleted successfully.');
    }
}
