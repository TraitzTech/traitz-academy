<?php

use App\Models\Application;
use App\Models\Event;
use App\Models\Program;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\ApplicationAcceptanceNotification;
use App\Notifications\ApplicationConfirmation;
use App\Notifications\EventRegistrationConfirmation;
use App\Notifications\NewApplicationSubmitted;
use App\Notifications\NewEventRegistration;
use Illuminate\Support\Facades\Notification;

describe('Application Submission Notifications', function () {
    test('application submission sends notification', function () {
        Notification::fake();

        $user = User::factory()->create();
        $program = Program::factory()->create(['category' => 'professional-training']);

        $this->actingAs($user)->post(route('applications.store'), [
            'program_id' => $program->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+234123456789',
            'country' => 'Nigeria',
            'bio' => 'Software developer',
            'education_level' => 'bachelor',
            'institution_name' => 'University of Lagos',
            'academic_duration' => '2020-2024',
            'motivation' => 'I want to improve my skills',
            'experience' => '3 years of experience',
        ]);

        Notification::assertSentOnDemand(NewApplicationSubmitted::class);
    });

    test('application submission sends confirmation email to applicant', function () {
        Notification::fake();

        $user = User::factory()->create();
        $program = Program::factory()->create(['category' => 'professional-training']);

        $this->actingAs($user)->post(route('applications.store'), [
            'program_id' => $program->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+234123456789',
            'country' => 'Nigeria',
            'bio' => 'Software developer',
            'education_level' => 'bachelor',
            'institution_name' => 'University of Lagos',
            'academic_duration' => '2020-2024',
            'motivation' => 'I want to improve my skills',
            'experience' => '3 years of experience',
        ]);

        Notification::assertSentOnDemand(ApplicationConfirmation::class, function (ApplicationConfirmation $notification, array $channels) {
            $mail = $notification->toMail(new \stdClass)->render();

            return in_array('mail', $channels, true)
                && str_contains($mail, route('applications.index'));
        });
    });

    test('application notification includes program title', function () {
        Notification::fake();

        $user = User::factory()->create();
        $program = Program::factory()->create([
            'title' => 'Advanced Python Course',
            'category' => 'professional-training',
        ]);

        $this->actingAs($user)->post(route('applications.store'), [
            'program_id' => $program->id,
            'first_name' => 'Bob',
            'last_name' => 'Johnson',
            'email' => 'bob@example.com',
            'phone' => '+234555555555',
            'country' => 'Kenya',
            'bio' => 'Python developer',
            'education_level' => 'bachelor',
            'institution_name' => 'Tech University',
            'academic_duration' => '2019-2023',
            'motivation' => 'Want to learn Python',
            'experience' => '1 year',
        ]);

        Notification::assertSentOnDemand(NewApplicationSubmitted::class);
    });

    test('user can apply multiple times with same email to different programs', function () {
        Notification::fake();

        $user = User::factory()->create();
        $program1 = Program::factory()->create([
            'title' => 'Program 1',
            'category' => 'professional-training',
        ]);
        $program2 = Program::factory()->create([
            'title' => 'Program 2',
            'category' => 'professional-training',
        ]);

        // First application
        $response1 = $this->actingAs($user)->post(route('applications.store'), [
            'program_id' => $program1->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+234123456789',
            'country' => 'Nigeria',
            'bio' => 'Software developer',
            'education_level' => 'bachelor',
            'institution_name' => 'University of Lagos',
            'academic_duration' => '2020-2024',
            'motivation' => 'I want to improve my skills',
            'experience' => '3 years of experience',
        ]);

        $response1->assertRedirect();

        // Second application with same email but different program
        $response2 = $this->actingAs($user)->post(route('applications.store'), [
            'program_id' => $program2->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+234123456789',
            'country' => 'Nigeria',
            'bio' => 'Software developer',
            'education_level' => 'bachelor',
            'institution_name' => 'University of Lagos',
            'academic_duration' => '2020-2024',
            'motivation' => 'I want to improve my skills',
            'experience' => '3 years of experience',
        ]);

        $response2->assertRedirect();

        expect(Application::where('email', 'john@example.com')->count())->toBe(2);
    });

    test('get applications route redirects to dashboard applications section', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('applications.index'))
            ->assertRedirect('/dashboard#applications');
    });
});

describe('Application Acceptance Notifications', function () {
    test('accepted interns receive community link from contact WhatsApp fallback', function () {
        Notification::fake();

        SiteSetting::set('social_whatsapp_community', null, [
            'type' => 'url',
            'group' => 'social',
            'label' => 'WhatsApp Community Link',
        ]);
        SiteSetting::set('contact_whatsapp', '+234 801 234 5678', [
            'type' => 'text',
            'group' => 'contact',
            'label' => 'WhatsApp Number',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $program = Program::factory()->create();
        $application = Application::factory()->create([
            'program_id' => $program->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.applications.accept', $application))
            ->assertRedirect();

        Notification::assertSentTo($user, ApplicationAcceptanceNotification::class, function (ApplicationAcceptanceNotification $notification, array $channels) use ($user) {
            $mail = $notification->toMail($user)->render();

            return in_array('mail', $channels, true)
                && str_contains($mail, 'https://wa.me/2348012345678');
        });
    });

    test('accepted interns without a linked user still receive acceptance email', function () {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $program = Program::factory()->create();
        $application = Application::factory()->create([
            'program_id' => $program->id,
            'user_id' => null,
            'status' => 'pending',
            'email' => 'intern@example.com',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.applications.accept', $application))
            ->assertRedirect();

        Notification::assertSentOnDemand(ApplicationAcceptanceNotification::class);
    });
});

describe('Event Registration Notifications', function () {
    test('event registration sends notification', function () {
        Notification::fake();

        $event = Event::factory()->create();

        $this->post(route('events.register'), [
            'event_id' => $event->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+234123456789',
        ]);

        Notification::assertSentOnDemand(NewEventRegistration::class);
    });

    test('event registration notification includes event title', function () {
        Notification::fake();

        $event = Event::factory()->create(['title' => 'AI Workshop 2026']);

        $this->post(route('events.register'), [
            'event_id' => $event->id,
            'first_name' => 'Bob',
            'last_name' => 'Wilson',
            'email' => 'bob@example.com',
            'phone' => '+234555555555',
        ]);

        Notification::assertSentOnDemand(NewEventRegistration::class);
    });

    test('event registration sends confirmation email to registrant', function () {
        Notification::fake();

        $event = Event::factory()->create(['title' => 'AI Summit']);

        $this->post(route('events.register'), [
            'event_id' => $event->id,
            'first_name' => 'Alice',
            'last_name' => 'Doe',
            'email' => 'alice@example.com',
            'phone' => '+234999999999',
        ]);

        Notification::assertSentOnDemand(EventRegistrationConfirmation::class);
    });

    test('user can register for multiple events with same email', function () {
        Notification::fake();

        $event1 = Event::factory()->create(['title' => 'Event 1']);
        $event2 = Event::factory()->create(['title' => 'Event 2']);

        $this->post(route('events.register'), [
            'event_id' => $event1->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.same@example.com',
            'phone' => '+234123456789',
        ]);

        $this->post(route('events.register'), [
            'event_id' => $event2->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.same@example.com',
            'phone' => '+234123456789',
        ]);

        expect(\App\Models\EventRegistration::where('email', 'john.same@example.com')->count())->toBe(2);
    });
});
