<?php

use App\Models\Application;
use App\Models\Payment;
use App\Models\Program;
use App\Models\User;
use App\Notifications\AdminAccountCredentialsNotification;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

it('allows executives to create coordinator users and sends login credentials automatically', function () {
    Notification::fake();

    $cto = User::factory()->create(['role' => User::ROLE_CTO]);

    $response = $this->actingAs($cto)->post(route('admin.users.store'), [
        'name' => 'Program Coordinator',
        'email' => 'coordinator@example.com',
        'phone' => '+237670000001',
        'role' => User::ROLE_PROGRAM_COORDINATOR,
        'password' => '',
        'password_confirmation' => '',
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('success');

    $createdUser = User::query()->where('email', 'coordinator@example.com')->first();

    expect($createdUser)->not->toBeNull();
    expect($createdUser->role)->toBe(User::ROLE_PROGRAM_COORDINATOR);

    Notification::assertSentTo($createdUser, AdminAccountCredentialsNotification::class);
});

it('prevents program coordinator from managing users while allowing cto and ceo', function () {
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);
    $cto = User::factory()->create(['role' => User::ROLE_CTO]);
    $ceo = User::factory()->create(['role' => User::ROLE_CEO]);

    $this->actingAs($coordinator)
        ->get(route('admin.users.index'))
        ->assertForbidden();

    $this->actingAs($cto)
        ->get(route('admin.users.index'))
        ->assertSuccessful();

    $this->actingAs($ceo)
        ->get(route('admin.users.index'))
        ->assertSuccessful();
});

it('limits program coordinator payment visibility to payments they collected', function () {
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);
    $otherExecutive = User::factory()->create(['role' => User::ROLE_CEO]);
    $student = User::factory()->create();
    $program = Program::factory()->create(['price' => 100000, 'max_installments' => 2]);
    $application = Application::factory()->create([
        'user_id' => $student->id,
        'program_id' => $program->id,
        'status' => 'accepted',
        'phone' => '670000000',
    ]);

    Payment::factory()->create([
        'application_id' => $application->id,
        'user_id' => $student->id,
        'program_id' => $program->id,
        'manual_entry' => true,
        'recorded_by' => $coordinator->id,
        'updated_by' => $coordinator->id,
        'status' => 'successful',
        'amount' => 30000,
    ]);

    Payment::factory()->create([
        'application_id' => $application->id,
        'user_id' => $student->id,
        'program_id' => $program->id,
        'manual_entry' => true,
        'recorded_by' => $otherExecutive->id,
        'updated_by' => $otherExecutive->id,
        'status' => 'successful',
        'amount' => 20000,
    ]);

    $response = $this->actingAs($coordinator)->get(route('admin.payments.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Payments/Index')
        ->has('payments.data', 1)
        ->where('payments.data.0.recorded_by.id', $coordinator->id)
    );
});

it('prevents program coordinator from updating a payment they did not collect', function () {
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);
    $otherExecutive = User::factory()->create(['role' => User::ROLE_CEO]);
    $student = User::factory()->create();
    $program = Program::factory()->create(['price' => 100000]);
    $application = Application::factory()->create([
        'user_id' => $student->id,
        'program_id' => $program->id,
        'status' => 'accepted',
    ]);

    $payment = Payment::factory()->create([
        'application_id' => $application->id,
        'user_id' => $student->id,
        'program_id' => $program->id,
        'manual_entry' => true,
        'recorded_by' => $otherExecutive->id,
        'updated_by' => $otherExecutive->id,
        'status' => 'successful',
    ]);

    $this->actingAs($coordinator)
        ->patch(route('admin.payments.update', $payment), [
            'reference' => $payment->reference,
            'receipt_number' => $payment->receipt_number,
            'payer_phone' => $payment->payer_phone,
            'provider' => $payment->provider,
            'payment_channel' => $payment->payment_channel,
            'amount' => $payment->amount,
            'payment_type' => $payment->payment_type,
            'installment_number' => $payment->installment_number,
            'total_installments' => $payment->total_installments,
            'status' => $payment->status,
            'manual_entry' => $payment->manual_entry,
        ])
        ->assertForbidden();
});

it('shows program coordinator collected amount as only their collected payments on dashboard', function () {
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);
    $ceo = User::factory()->create(['role' => User::ROLE_CEO]);
    $student = User::factory()->create();
    $program = Program::factory()->create(['price' => 100000]);
    $application = Application::factory()->create([
        'user_id' => $student->id,
        'program_id' => $program->id,
        'status' => 'accepted',
    ]);

    Payment::factory()->create([
        'application_id' => $application->id,
        'user_id' => $student->id,
        'program_id' => $program->id,
        'status' => 'successful',
        'manual_entry' => true,
        'recorded_by' => $coordinator->id,
        'updated_by' => $coordinator->id,
        'amount' => 35000,
    ]);

    Payment::factory()->create([
        'application_id' => $application->id,
        'user_id' => $student->id,
        'program_id' => $program->id,
        'status' => 'successful',
        'manual_entry' => true,
        'recorded_by' => $ceo->id,
        'updated_by' => $ceo->id,
        'amount' => 15000,
    ]);

    $response = $this->actingAs($coordinator)->get(route('admin.dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Dashboard')
        ->where('stats.total_collected', 35000)
        ->where('stats.collected_label', 'My Collected')
    );
});

it('allows executives to filter admin payments by collector role', function () {
    $viewer = User::factory()->create(['role' => User::ROLE_CTO]);
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);
    $ceo = User::factory()->create(['role' => User::ROLE_CEO]);
    $student = User::factory()->create();
    $program = Program::factory()->create(['price' => 100000]);
    $application = Application::factory()->create([
        'user_id' => $student->id,
        'program_id' => $program->id,
        'status' => 'accepted',
    ]);

    Payment::factory()->create([
        'application_id' => $application->id,
        'user_id' => $student->id,
        'program_id' => $program->id,
        'status' => 'successful',
        'manual_entry' => true,
        'recorded_by' => $coordinator->id,
        'updated_by' => $coordinator->id,
        'amount' => 22000,
    ]);

    Payment::factory()->create([
        'application_id' => $application->id,
        'user_id' => $student->id,
        'program_id' => $program->id,
        'status' => 'successful',
        'manual_entry' => true,
        'recorded_by' => $ceo->id,
        'updated_by' => $ceo->id,
        'amount' => 18000,
    ]);

    $response = $this->actingAs($viewer)->get(route('admin.payments.index', [
        'collector_role' => User::ROLE_PROGRAM_COORDINATOR,
    ]));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Payments/Index')
        ->where('filters.collector_role', User::ROLE_PROGRAM_COORDINATOR)
        ->has('payments.data', 1)
        ->where('payments.data.0.recorded_by.id', $coordinator->id)
    );
});

it('allows filtering payments by source manual or online', function () {
    $viewer = User::factory()->create(['role' => User::ROLE_CTO]);
    $student = User::factory()->create();
    $program = Program::factory()->create(['price' => 120000]);
    $application = Application::factory()->create([
        'user_id' => $student->id,
        'program_id' => $program->id,
        'status' => 'accepted',
    ]);

    Payment::factory()->create([
        'application_id' => $application->id,
        'user_id' => $student->id,
        'program_id' => $program->id,
        'manual_entry' => true,
        'status' => 'successful',
        'amount' => 30000,
    ]);

    $onlinePayment = Payment::factory()->create([
        'application_id' => $application->id,
        'user_id' => $student->id,
        'program_id' => $program->id,
        'manual_entry' => false,
        'status' => 'successful',
        'amount' => 45000,
    ]);

    $manualResponse = $this->actingAs($viewer)->get(route('admin.payments.index', [
        'payment_source' => 'manual',
    ]));

    $manualResponse->assertSuccessful();
    $manualResponse->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Payments/Index')
        ->where('filters.payment_source', 'manual')
        ->has('payments.data', 1)
        ->where('payments.data.0.manual_entry', true)
    );

    $onlineResponse = $this->actingAs($viewer)->get(route('admin.payments.index', [
        'payment_source' => 'online',
    ]));

    $onlineResponse->assertSuccessful();
    $onlineResponse->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Payments/Index')
        ->where('filters.payment_source', 'online')
        ->has('payments.data', 1)
        ->where('payments.data.0.manual_entry', false)
    );
});

it('allows only executives to export filtered payments as csv', function () {
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);
    $viewer = User::factory()->create(['role' => User::ROLE_CEO]);
    $student = User::factory()->create();
    $program = Program::factory()->create(['price' => 120000]);
    $application = Application::factory()->create([
        'user_id' => $student->id,
        'program_id' => $program->id,
        'status' => 'accepted',
    ]);

    $manualPayment = Payment::factory()->create([
        'application_id' => $application->id,
        'user_id' => $student->id,
        'program_id' => $program->id,
        'manual_entry' => true,
        'status' => 'successful',
        'amount' => 22000,
    ]);

    $onlinePayment = Payment::factory()->create([
        'application_id' => $application->id,
        'user_id' => $student->id,
        'program_id' => $program->id,
        'manual_entry' => false,
        'status' => 'successful',
        'amount' => 50000,
    ]);

    $this->actingAs($coordinator)
        ->get(route('admin.payments.export'))
        ->assertForbidden();

    $response = $this->actingAs($viewer)
        ->get(route('admin.payments.export', ['payment_source' => 'manual']));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Receipt Number');
    expect($csv)->toContain($manualPayment->reference);
    expect($csv)->not->toContain($onlinePayment->reference);
});
