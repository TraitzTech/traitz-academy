<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuizBuilderController extends Controller
{
    public function show(Course $course, CourseLesson $lesson): Response
    {
        $this->authorizeLesson($course, $lesson);

        $quiz = Quiz::query()
            ->where('course_id', $course->id)
            ->where('lesson_id', $lesson->id)
            ->with(['questions' => fn ($q) => $q->ordered()])
            ->first();

        return Inertia::render('Tutor/Quizzes/Builder', [
            'course' => $course->only(['id', 'title']),
            'lesson' => $lesson->only(['id', 'title', 'type']),
            'quiz' => $quiz,
        ]);
    }

    public function upsertMeta(Request $request, Course $course, CourseLesson $lesson): RedirectResponse
    {
        $this->authorizeLesson($course, $lesson);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'pass_mark_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:50'],
            'is_required' => ['boolean'],
            'reveal_answers' => ['boolean'],
        ]);

        Quiz::updateOrCreate(
            ['course_id' => $course->id, 'lesson_id' => $lesson->id],
            [
                ...$validated,
                'is_required' => $request->boolean('is_required'),
                'reveal_answers' => $request->boolean('reveal_answers', true),
            ]
        );

        return back()->with('success', 'Quiz settings saved.');
    }

    public function storeQuestion(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorizeQuiz($quiz);

        $validated = $this->validateQuestion($request);
        $payload = $this->normalizeQuestionPayload($validated);

        $sortOrder = (int) $quiz->questions()->max('sort_order') + 1;

        $quiz->questions()->create([
            ...$payload,
            'sort_order' => $sortOrder,
        ]);

        return back()->with('success', 'Question added.');
    }

    public function updateQuestion(Request $request, Quiz $quiz, QuizQuestion $question): RedirectResponse
    {
        $this->authorizeQuiz($quiz);
        abort_unless($question->quiz_id === $quiz->id, 404);

        $validated = $this->validateQuestion($request);
        $payload = $this->normalizeQuestionPayload($validated);

        $question->update($payload);

        return back()->with('success', 'Question updated.');
    }

    public function destroyQuestion(Quiz $quiz, QuizQuestion $question): RedirectResponse
    {
        $this->authorizeQuiz($quiz);
        abort_unless($question->quiz_id === $quiz->id, 404);

        $question->delete();

        $quiz->questions()->ordered()->each(function (QuizQuestion $q, int $idx) {
            $q->update(['sort_order' => $idx]);
        });

        return back()->with('success', 'Question removed.');
    }

    public function reorderQuestions(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorizeQuiz($quiz);

        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:quiz_questions,id'],
        ]);

        foreach ($request->order as $index => $questionId) {
            $quiz->questions()->where('id', $questionId)->update(['sort_order' => $index]);
        }

        return back()->with('success', 'Question order updated.');
    }

    private function validateQuestion(Request $request): array
    {
        return $request->validate([
            'question' => ['required', 'string'],
            'type' => ['required', 'in:multiple_choice,multiple_select,true_false,short_answer'],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable', 'string', 'max:500'],
            'correct_answer' => ['required'],
            'explanation' => ['nullable', 'string'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
    }

    private function normalizeQuestionPayload(array $validated): array
    {
        $type = $validated['type'];

        $options = collect($validated['options'] ?? [])
            ->map(fn ($v) => is_string($v) ? trim($v) : $v)
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->values()
            ->all();

        $correct = $validated['correct_answer'];

        if ($type === 'multiple_choice') {
            $correct = [is_array($correct) ? (int) ($correct[0] ?? 0) : (int) $correct];
        } elseif ($type === 'multiple_select') {
            $correct = collect(is_array($correct) ? $correct : [$correct])
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->sort()
                ->values()
                ->all();
        } elseif ($type === 'true_false') {
            $answer = is_array($correct) ? ($correct[0] ?? 'false') : $correct;
            $answer = in_array((string) $answer, ['true', '1', 'yes'], true) ? 'true' : 'false';
            $correct = [$answer];
            $options = ['True', 'False'];
        } else { // short_answer
            $answer = is_array($correct) ? ($correct[0] ?? '') : $correct;
            $correct = [trim((string) $answer)];
            $options = [];
        }

        return [
            'question' => $validated['question'],
            'type' => $type,
            'options' => $options,
            'correct_answer' => $correct,
            'explanation' => $validated['explanation'] ?? null,
            'points' => $validated['points'],
        ];
    }

    private function authorizeLesson(Course $course, CourseLesson $lesson): void
    {
        abort_unless($course->instructor_id === auth()->id(), 403);
        abort_unless($lesson->course_id === $course->id, 404);
        abort_unless($lesson->type === 'quiz', 422, 'This lesson is not a quiz lesson.');
    }

    private function authorizeQuiz(Quiz $quiz): void
    {
        abort_unless($quiz->course && $quiz->course->instructor_id === auth()->id(), 403);
    }
}
