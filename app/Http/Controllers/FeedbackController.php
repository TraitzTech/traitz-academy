<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitFeedbackRequest;
use App\Models\FeedbackAnswer;
use App\Models\FeedbackForm;
use App\Models\FeedbackResponse;
use App\Notifications\FeedbackThankYouNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\AnonymousNotifiable;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackController extends Controller
{
    public function fill(string $slug): Response|RedirectResponse
    {
        $form = FeedbackForm::where('slug', $slug)->with('questions')->firstOrFail();

        if (! $form->isOpen()) {
            return Inertia::render('Feedback/Closed', [
                'form' => $form->only('title', 'description'),
            ]);
        }

        return Inertia::render('Feedback/Fill', [
            'form' => $form,
            'authUser' => auth()->check() ? auth()->user()->only('name', 'email') : null,
        ]);
    }

    public function submit(SubmitFeedbackRequest $request, string $slug): RedirectResponse
    {
        $form = FeedbackForm::where('slug', $slug)->with('questions')->firstOrFail();

        if (! $form->isOpen()) {
            return back()->with('error', 'This feedback form is no longer accepting responses.');
        }

        $validated = $request->validated();
        $isAnonymous = (bool) ($validated['is_anonymous'] ?? false);

        $response = FeedbackResponse::create([
            'feedback_form_id' => $form->id,
            'user_id' => auth()->id(),
            'is_anonymous' => $isAnonymous,
            'respondent_name' => $isAnonymous ? null : ($validated['respondent_name'] ?? auth()->user()?->name),
            'respondent_email' => $isAnonymous ? null : ($validated['respondent_email'] ?? auth()->user()?->email),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $answers = $validated['answers'] ?? [];
        foreach ($form->questions as $question) {
            FeedbackAnswer::create([
                'feedback_response_id' => $response->id,
                'feedback_question_id' => $question->id,
                'answer' => $answers[$question->id] ?? null,
            ]);
        }

        // Send thank-you email if configured and we have an email
        if ($form->send_thank_you_email && ! $isAnonymous) {
            $email = $response->respondent_email ?? auth()->user()?->email;
            $name = $response->respondent_name ?? auth()->user()?->name;

            if ($email) {
                if (auth()->check()) {
                    auth()->user()->notify(new FeedbackThankYouNotification($form));
                } else {
                    (new AnonymousNotifiable)
                        ->route('mail', $email)
                        ->notify(new FeedbackThankYouNotification($form, $name));
                }
            }
        }

        return redirect()->route('feedback.thanks', $slug);
    }

    public function thanks(string $slug): Response
    {
        $form = FeedbackForm::where('slug', $slug)->firstOrFail();

        return Inertia::render('Feedback/Thanks', [
            'form' => $form->only('title', 'description'),
        ]);
    }
}
