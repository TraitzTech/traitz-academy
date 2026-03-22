<?php

use App\Models\FeedbackAnswer;
use App\Models\FeedbackForm;
use App\Models\FeedbackQuestion;
use App\Models\FeedbackResponse;
use App\Models\User;
use App\Notifications\FeedbackThankYouNotification;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

// -----------------------------------------------------------------------
// Access Control
// -----------------------------------------------------------------------

it('redirects unauthenticated users from admin feedback routes', function () {
    $this->get(route('admin.feedback.index'))->assertRedirect(route('login'));
});

it('forbids regular users from accessing admin feedback routes', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)->get(route('admin.feedback.index'))->assertForbidden();
});

it('allows CEO, CTO, and program coordinators to access admin feedback index', function () {
    $ceo = User::factory()->create(['role' => User::ROLE_CEO]);
    $cto = User::factory()->create(['role' => User::ROLE_CTO]);
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);

    $this->actingAs($ceo)->get(route('admin.feedback.index'))->assertSuccessful();
    $this->actingAs($cto)->get(route('admin.feedback.index'))->assertSuccessful();
    $this->actingAs($coordinator)->get(route('admin.feedback.index'))->assertSuccessful();
});

// -----------------------------------------------------------------------
// Admin: Index
// -----------------------------------------------------------------------

it('renders the admin feedback index with expected props', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CEO]);
    FeedbackForm::factory()->count(3)->create(['created_by' => $admin->id]);

    $this->actingAs($admin)
        ->get(route('admin.feedback.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Feedback/Index')
            ->has('forms')
            ->has('forms.data', 3)
            ->has('filters')
        );
});

it('filters feedback forms by search term', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CEO]);
    FeedbackForm::factory()->create(['title' => 'Internship Experience', 'created_by' => $admin->id]);
    FeedbackForm::factory()->create(['title' => 'Program Satisfaction', 'created_by' => $admin->id]);

    $this->actingAs($admin)
        ->get(route('admin.feedback.index', ['search' => 'Internship']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('forms.data', 1)
            ->where('forms.data.0.title', 'Internship Experience')
        );
});

// -----------------------------------------------------------------------
// Admin: Create & Store
// -----------------------------------------------------------------------

it('renders the create feedback form page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CEO]);

    $this->actingAs($admin)
        ->get(route('admin.feedback.create'))
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Feedback/Create'));
});

it('admin can create a feedback form with text questions', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CEO]);

    $this->actingAs($admin)
        ->post(route('admin.feedback.store'), [
            'title' => 'Q1 Intern Feedback',
            'description' => 'Tell us how it went.',
            'is_active' => true,
            'allow_anonymous' => true,
            'send_thank_you_email' => true,
            'closes_at' => null,
            'questions' => [
                ['question' => 'How was your experience?', 'type' => 'text', 'options' => null, 'required' => true],
            ],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('feedback_forms', ['title' => 'Q1 Intern Feedback', 'created_by' => $admin->id]);
    $this->assertDatabaseHas('feedback_questions', ['question' => 'How was your experience?', 'type' => 'text']);
});

it('admin can create a feedback form with multiple choice questions', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CEO]);

    $this->actingAs($admin)
        ->post(route('admin.feedback.store'), [
            'title' => 'Program Rating',
            'is_active' => true,
            'allow_anonymous' => false,
            'send_thank_you_email' => false,
            'closes_at' => null,
            'questions' => [
                [
                    'question' => 'How would you rate the program?',
                    'type' => 'multiple_choice',
                    'options' => ['Excellent', 'Good', 'Average', 'Poor'],
                    'required' => true,
                ],
            ],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('feedback_questions', [
        'question' => 'How would you rate the program?',
        'type' => 'multiple_choice',
    ]);
});

it('rejects store when title is missing', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CEO]);

    $this->actingAs($admin)
        ->post(route('admin.feedback.store'), [
            'title' => '',
            'questions' => [
                ['question' => 'Any question?', 'type' => 'text', 'options' => [], 'required' => true],
            ],
        ])
        ->assertSessionHasErrors('title');
});

it('rejects store when no questions are provided', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CEO]);

    $this->actingAs($admin)
        ->post(route('admin.feedback.store'), [
            'title' => 'A Form',
            'questions' => [],
        ])
        ->assertSessionHasErrors('questions');
});

// -----------------------------------------------------------------------
// Admin: Show
// -----------------------------------------------------------------------

it('renders the feedback show page with analytics and responses', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CEO]);
    $form = FeedbackForm::factory()->create(['created_by' => $admin->id]);
    FeedbackQuestion::factory()->create(['feedback_form_id' => $form->id, 'type' => 'text']);

    $this->actingAs($admin)
        ->get(route('admin.feedback.show', $form))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Feedback/Show')
            ->has('form')
            ->has('analytics')
            ->has('responses')
            ->has('stats')
            ->has('shareUrl')
        );
});

// -----------------------------------------------------------------------
// Admin: Edit & Update
// -----------------------------------------------------------------------

it('renders the edit feedback form page', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CEO]);
    $form = FeedbackForm::factory()->create(['created_by' => $admin->id]);
    FeedbackQuestion::factory()->create(['feedback_form_id' => $form->id]);

    $this->actingAs($admin)
        ->get(route('admin.feedback.edit', $form))
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Feedback/Edit'));
});

it('admin can update a feedback form', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CEO]);
    $form = FeedbackForm::factory()->create(['title' => 'Old Title', 'created_by' => $admin->id]);
    FeedbackQuestion::factory()->create(['feedback_form_id' => $form->id, 'type' => 'text']);

    $this->actingAs($admin)
        ->put(route('admin.feedback.update', $form), [
            'title' => 'Updated Title',
            'is_active' => true,
            'allow_anonymous' => true,
            'send_thank_you_email' => false,
            'closes_at' => null,
            'questions' => [
                ['question' => 'Updated question?', 'type' => 'text', 'options' => null, 'required' => true],
            ],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('feedback_forms', ['id' => $form->id, 'title' => 'Updated Title']);
});

// -----------------------------------------------------------------------
// Admin: Toggle Status & Destroy
// -----------------------------------------------------------------------

it('admin can toggle feedback form status', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CEO]);
    $form = FeedbackForm::factory()->create(['is_active' => true, 'created_by' => $admin->id]);

    $this->actingAs($admin)
        ->post(route('admin.feedback.toggle-status', $form))
        ->assertRedirect();

    $this->assertDatabaseHas('feedback_forms', ['id' => $form->id, 'is_active' => false]);
});

it('admin can delete a feedback form and its responses', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CEO]);
    $form = FeedbackForm::factory()->create(['created_by' => $admin->id]);

    $this->actingAs($admin)
        ->delete(route('admin.feedback.destroy', $form))
        ->assertRedirect(route('admin.feedback.index'));

    $this->assertDatabaseMissing('feedback_forms', ['id' => $form->id]);
});

it('admin can delete an individual feedback response', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CEO]);
    $form = FeedbackForm::factory()->create(['created_by' => $admin->id]);
    $question = FeedbackQuestion::factory()->create(['feedback_form_id' => $form->id]);
    $response = FeedbackResponse::factory()->create(['feedback_form_id' => $form->id]);
    $answer = FeedbackAnswer::factory()->create([
        'feedback_response_id' => $response->id,
        'feedback_question_id' => $question->id,
        'answer' => 'Test answer',
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.feedback.responses.destroy', [$form, $response]))
        ->assertRedirect();

    $this->assertDatabaseMissing('feedback_responses', ['id' => $response->id]);
    $this->assertDatabaseMissing('feedback_answers', ['id' => $answer->id]);
});

it('cannot delete a response that does not belong to the given form', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CEO]);
    $form = FeedbackForm::factory()->create(['created_by' => $admin->id]);
    $otherForm = FeedbackForm::factory()->create(['created_by' => $admin->id]);
    $response = FeedbackResponse::factory()->create(['feedback_form_id' => $otherForm->id]);

    $this->actingAs($admin)
        ->delete(route('admin.feedback.responses.destroy', [$form, $response]))
        ->assertNotFound();
});

// -----------------------------------------------------------------------
// Public: Fill Page
// -----------------------------------------------------------------------

it('renders the fill page for an active feedback form', function () {
    $form = FeedbackForm::factory()->create(['is_active' => true]);
    FeedbackQuestion::factory()->create(['feedback_form_id' => $form->id]);

    $this->get(route('feedback.fill', $form->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Feedback/Fill')
            ->has('form')
            ->has('authUser')
        );
});

it('renders the closed page for an inactive feedback form', function () {
    $form = FeedbackForm::factory()->inactive()->create();

    $this->get(route('feedback.fill', $form->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Feedback/Closed'));
});

it('renders the closed page for an expired feedback form', function () {
    $form = FeedbackForm::factory()->closed()->create();

    $this->get(route('feedback.fill', $form->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Feedback/Closed'));
});

it('returns 404 for a non-existent feedback form slug', function () {
    $this->get(route('feedback.fill', 'no-such-form'))->assertNotFound();
});

// -----------------------------------------------------------------------
// Public: Submit
// -----------------------------------------------------------------------

it('can submit anonymous feedback', function () {
    Notification::fake();

    $form = FeedbackForm::factory()->create(['allow_anonymous' => true]);
    $question = FeedbackQuestion::factory()->create(['feedback_form_id' => $form->id, 'type' => 'text', 'required' => false]);

    $this->post(route('feedback.submit', $form->slug), [
        'is_anonymous' => true,
        'answers' => [$question->id => 'Great experience!'],
    ])->assertRedirect(route('feedback.thanks', $form->slug));

    $this->assertDatabaseHas('feedback_responses', [
        'feedback_form_id' => $form->id,
        'is_anonymous' => true,
    ]);

    $this->assertDatabaseHas('feedback_answers', [
        'feedback_question_id' => $question->id,
        'answer' => 'Great experience!',
    ]);

    Notification::assertNothingSent();
});

it('can submit feedback as a named guest and queues thank-you email', function () {
    Notification::fake();

    $form = FeedbackForm::factory()->create(['allow_anonymous' => true, 'send_thank_you_email' => true]);
    $question = FeedbackQuestion::factory()->create(['feedback_form_id' => $form->id, 'type' => 'text', 'required' => false]);

    $this->post(route('feedback.submit', $form->slug), [
        'is_anonymous' => false,
        'respondent_name' => 'Jane Doe',
        'respondent_email' => 'jane@example.com',
        'answers' => [$question->id => 'Loved it!'],
    ])->assertRedirect(route('feedback.thanks', $form->slug));

    $this->assertDatabaseHas('feedback_responses', [
        'feedback_form_id' => $form->id,
        'respondent_name' => 'Jane Doe',
        'respondent_email' => 'jane@example.com',
        'is_anonymous' => false,
    ]);

    Notification::assertSentOnDemand(FeedbackThankYouNotification::class);
});

it('does not send thank-you email when anonymous', function () {
    Notification::fake();

    $form = FeedbackForm::factory()->create(['allow_anonymous' => true, 'send_thank_you_email' => true]);
    $question = FeedbackQuestion::factory()->create(['feedback_form_id' => $form->id, 'type' => 'text', 'required' => false]);

    $this->post(route('feedback.submit', $form->slug), [
        'is_anonymous' => true,
        'answers' => [$question->id => 'Nice!'],
    ]);

    Notification::assertNothingSent();
});

it('does not send thank-you email when form has it disabled', function () {
    Notification::fake();

    $form = FeedbackForm::factory()->create(['send_thank_you_email' => false]);
    $question = FeedbackQuestion::factory()->create(['feedback_form_id' => $form->id, 'type' => 'text', 'required' => false]);

    $this->post(route('feedback.submit', $form->slug), [
        'is_anonymous' => false,
        'respondent_name' => 'John',
        'respondent_email' => 'john@example.com',
        'answers' => [$question->id => 'Good'],
    ]);

    Notification::assertNothingSent();
});

it('can submit feedback as an authenticated user and queues thank-you email', function () {
    Notification::fake();

    $user = User::factory()->create();
    $form = FeedbackForm::factory()->create(['send_thank_you_email' => true]);
    $question = FeedbackQuestion::factory()->create(['feedback_form_id' => $form->id, 'type' => 'text', 'required' => false]);

    $this->actingAs($user)
        ->post(route('feedback.submit', $form->slug), [
            'is_anonymous' => false,
            'answers' => [$question->id => 'Very helpful!'],
        ])->assertRedirect(route('feedback.thanks', $form->slug));

    $this->assertDatabaseHas('feedback_responses', [
        'feedback_form_id' => $form->id,
        'user_id' => $user->id,
        'is_anonymous' => false,
    ]);

    Notification::assertSentTo($user, FeedbackThankYouNotification::class);
});

it('creates one answer record per question on submission', function () {
    $form = FeedbackForm::factory()->create(['allow_anonymous' => true]);
    $q1 = FeedbackQuestion::factory()->create(['feedback_form_id' => $form->id, 'type' => 'text', 'required' => false]);
    $q2 = FeedbackQuestion::factory()->create(['feedback_form_id' => $form->id, 'type' => 'text', 'required' => false]);

    $this->post(route('feedback.submit', $form->slug), [
        'is_anonymous' => true,
        'answers' => [
            $q1->id => 'Answer one',
            $q2->id => 'Answer two',
        ],
    ]);

    expect(FeedbackAnswer::whereIn('feedback_question_id', [$q1->id, $q2->id])->count())->toBe(2);
});

it('cannot submit to a closed feedback form', function () {
    $form = FeedbackForm::factory()->inactive()->create();

    $this->post(route('feedback.submit', $form->slug), [
        'is_anonymous' => true,
        'answers' => [],
    ])->assertRedirect();

    $this->assertDatabaseMissing('feedback_responses', ['feedback_form_id' => $form->id]);
});

// -----------------------------------------------------------------------
// Public: Thanks Page
// -----------------------------------------------------------------------

it('renders the thanks page', function () {
    $form = FeedbackForm::factory()->create();

    $this->get(route('feedback.thanks', $form->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Feedback/Thanks')
            ->has('form')
        );
});
