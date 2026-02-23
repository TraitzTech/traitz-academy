<?php

use App\Models\Application;
use App\Models\Event;
use App\Models\Program;
use App\Models\User;
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

        Notification::assertSentOnDemand(ApplicationConfirmation::class);
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
