<?php

use App\Models\Application;
use App\Models\Internship;
use App\Models\InternshipAttendance;
use App\Models\LogbookEntry;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

function officeConfigAt(float $lat = 4.0511, float $lng = 9.7679): void
{
    config([
        'internship.office.latitude' => $lat,
        'internship.office.longitude' => $lng,
        'internship.office.enforce_location' => true,
        'internship.office.radius_meters' => 150,
        'internship.office.tolerance_meters' => 100,
    ]);
}

it('lets an intern clock in from the office', function () {
    officeConfigAt();
    $user = User::factory()->create();
    Internship::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    $this->actingAs($user)
        ->post('/dashboard/internship/attendance/clock-in', ['latitude' => 4.0511, 'longitude' => 9.7679])
        ->assertSessionHasNoErrors();

    expect(InternshipAttendance::query()->whereNotNull('clock_in_at')->count())->toBe(1);
});

it('rejects clock-in from outside the office geofence', function () {
    officeConfigAt();
    $user = User::factory()->create();
    Internship::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    // ~5km away.
    $this->actingAs($user)
        ->post('/dashboard/internship/attendance/clock-in', ['latitude' => 4.1000, 'longitude' => 9.7679])
        ->assertSessionHasErrors('location');

    expect(InternshipAttendance::query()->whereNotNull('clock_in_at')->count())->toBe(0);
});

it('blocks clock-out until the logbook is submitted', function () {
    officeConfigAt();
    $user = User::factory()->create();
    $internship = Internship::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    InternshipAttendance::factory()->create([
        'internship_id' => $internship->id,
        'date' => now()->toDateString(),
        'clock_in_at' => now()->subHours(2),
    ]);

    // No logbook yet → blocked.
    $this->actingAs($user)
        ->post('/dashboard/internship/attendance/clock-out')
        ->assertSessionHasErrors('logbook');

    expect(InternshipAttendance::query()->whereNotNull('clock_out_at')->count())->toBe(0);

    // Submit the logbook, then clock out succeeds.
    LogbookEntry::factory()->submitted()->create([
        'internship_id' => $internship->id,
        'date' => now()->toDateString(),
    ]);

    $this->actingAs($user)
        ->post('/dashboard/internship/attendance/clock-out')
        ->assertSessionHasNoErrors();

    expect(InternshipAttendance::query()->whereNotNull('clock_out_at')->count())->toBe(1);
});

it('creates an internship when an internship application is accepted', function () {
    Notification::fake();

    $exec = User::factory()->create(['role' => User::ROLE_CTO]);
    $applicant = User::factory()->create();
    $program = Program::factory()->create(['category' => 'professional-internship']);
    $application = Application::query()->create([
        'program_id' => $program->id,
        'user_id' => $applicant->id,
        'first_name' => 'A', 'last_name' => 'B',
        'email' => $applicant->email, 'phone' => '123', 'country' => 'CM',
        'status' => 'pending',
    ]);

    $this->actingAs($exec)
        ->post("/admin/applications/{$application->id}/accept")
        ->assertRedirect();

    expect(Internship::query()->where('user_id', $applicant->id)->where('program_id', $program->id)->exists())->toBeTrue();
});

it('lets the assigned supervisor view their intern but forbids others', function () {
    $supervisor = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    $other = User::factory()->create(['role' => User::ROLE_SUPERVISOR]);
    $internship = Internship::factory()->create(['supervisor_id' => $supervisor->id]);

    $this->actingAs($supervisor)->get("/supervisor/interns/{$internship->id}")->assertOk();
    $this->actingAs($other)->get("/supervisor/interns/{$internship->id}")->assertForbidden();
});
