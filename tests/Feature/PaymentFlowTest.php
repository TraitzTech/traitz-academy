<?php

use App\Models\Application;
use App\Models\Payment;
use App\Models\Program;
use App\Models\User;
use App\Notifications\PaymentReceiptNotification;
use App\Notifications\PaymentReminderNotification;
use App\Support\Payments\Contracts\PaymentGateway;
use App\Support\Payments\DTOs\PaymentGatewayResult;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

class CapturingPaymentGateway implements PaymentGateway
{
    /**
     * @var array<string, mixed>
     */
    public array $capturedPayload = [];

    public function collect(array $payload): PaymentGatewayResult
    {
        $this->capturedPayload = $payload;

        return new PaymentGatewayResult(
            operationSuccessful: true,
            transactionSuccessful: true,
            transactionId: 'trx-captured-123',
            message: 'ok',
            rawResponse: ['ok' => true],
        );
    }
}

it('shows checkout page for accepted application', function () {
    $user = User::factory()->create();
    $program = Program::factory()->create([
        'price' => 100000,
        'max_installments' => 4,
    ]);

    $application = Application::factory()->create([
        'user_id' => $user->id,
        'program_id' => $program->id,
        'status' => 'accepted',
    ]);

    $response = $this->actingAs($user)->get(route('payments.checkout', $application));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Payments/Checkout')
        ->where('summary.remaining_amount', 100000)
        ->where('summary.max_installments', 4)
    );
});

it('records a successful installment payment and generates a receipt', function () {
    $user = User::factory()->create();
    $program = Program::factory()->create([
        'price' => 100000,
        'max_installments' => 4,
    ]);

    $application = Application::factory()->create([
        'user_id' => $user->id,
        'program_id' => $program->id,
        'status' => 'accepted',
        'phone' => '670000000',
    ]);

    app()->bind(PaymentGateway::class, fn () => new class implements PaymentGateway
    {
        public function collect(array $payload): PaymentGatewayResult
        {
            return new PaymentGatewayResult(
                operationSuccessful: true,
                transactionSuccessful: true,
                transactionId: 'trx-test-123',
                message: 'ok',
                rawResponse: ['ok' => true],
            );
        }
    });

    $response = $this->actingAs($user)->post(route('payments.store', $application), [
        'payer_phone' => '670000000',
        'provider' => 'MTN',
        'payment_mode' => 'installment',
    ]);

    $payment = Payment::first();

    $response->assertRedirect(route('payments.receipt', $payment));

    expect($payment)->not->toBeNull();
    expect($payment->status)->toBe('successful');
    expect((float) $payment->amount)->toBe(25000.0);
    expect($payment->installment_number)->toBe(1);
    expect($payment->total_installments)->toBe(4);
    expect($payment->receipt_number)->not->toBeNull();
});

it('prevents non-accepted applications from accessing checkout', function () {
    $user = User::factory()->create();
    $program = Program::factory()->create(['price' => 100000]);

    $application = Application::factory()->create([
        'user_id' => $user->id,
        'program_id' => $program->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->get(route('payments.checkout', $application));

    $response->assertRedirect(route('dashboard'));
});

it('shows payment tracking page for admins', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();
    $program = Program::factory()->create(['price' => 100000]);
    $application = Application::factory()->create([
        'user_id' => $user->id,
        'program_id' => $program->id,
        'status' => 'accepted',
    ]);

    Payment::factory()->create([
        'application_id' => $application->id,
        'user_id' => $user->id,
        'program_id' => $program->id,
        'status' => 'successful',
        'amount' => 100000,
    ]);

    $response = $this->actingAs($admin)->get('/admin/payments');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Payments/Index')
        ->has('payments.data', 1)
    );
});

it('sends a valid collect payload without location for mesomb', function () {
    $user = User::factory()->create();
    $program = Program::factory()->create([
        'price' => 50000,
        'max_installments' => 2,
    ]);

    $application = Application::factory()->create([
        'user_id' => $user->id,
        'program_id' => $program->id,
        'status' => 'accepted',
        'phone' => '670000000',
    ]);

    $gateway = new CapturingPaymentGateway;
    app()->instance(PaymentGateway::class, $gateway);

    $response = $this->actingAs($user)->post(route('payments.store', $application), [
        'payer_phone' => '670000000',
        'provider' => 'MTN',
        'payment_mode' => 'installment',
    ]);

    $response->assertRedirect();

    expect($gateway->capturedPayload)->toHaveKey('payer')
        ->and($gateway->capturedPayload)->toHaveKey('amount')
        ->and($gateway->capturedPayload)->toHaveKey('service')
        ->and($gateway->capturedPayload)->toHaveKey('customer')
        ->and($gateway->capturedPayload)->toHaveKey('products')
        ->and($gateway->capturedPayload)->not->toHaveKey('location');
});

it('allows admin to send payment reminder for accepted unpaid application', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();
    $program = Program::factory()->create([
        'price' => 100000,
        'max_installments' => 4,
    ]);

    $application = Application::factory()->create([
        'user_id' => $user->id,
        'program_id' => $program->id,
        'status' => 'accepted',
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.applications.payment-reminder', $application));

    $response->assertSessionHas('success');

    Notification::assertSentTo($user, PaymentReminderNotification::class);
});

it('sends bulk payment reminders only to eligible applications', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $program = Program::factory()->create([
        'price' => 100000,
        'max_installments' => 2,
    ]);

    $eligibleUser = User::factory()->create();
    $paidUser = User::factory()->create();

    $eligibleApplication = Application::factory()->create([
        'user_id' => $eligibleUser->id,
        'program_id' => $program->id,
        'status' => 'accepted',
    ]);

    $paidApplication = Application::factory()->create([
        'user_id' => $paidUser->id,
        'program_id' => $program->id,
        'status' => 'accepted',
    ]);

    Payment::factory()->create([
        'application_id' => $paidApplication->id,
        'user_id' => $paidUser->id,
        'program_id' => $program->id,
        'status' => 'successful',
        'amount' => 100000,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.applications.bulk-payment-reminder'), [
        'ids' => [$eligibleApplication->id, $paidApplication->id],
    ]);

    $response->assertSessionHas('success');

    Notification::assertSentToTimes($eligibleUser, PaymentReminderNotification::class, 1);
    Notification::assertNotSentTo($paidUser, PaymentReminderNotification::class);
});

it('allows admin to manually record onsite payment in same records', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();
    $program = Program::factory()->create([
        'price' => 90000,
        'max_installments' => 3,
    ]);

    $application = Application::factory()->create([
        'user_id' => $user->id,
        'program_id' => $program->id,
        'status' => 'accepted',
        'phone' => '670000000',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.payments.manual-store'), [
        'application_id' => $application->id,
        'amount' => 30000,
        'provider' => 'CASH',
        'payment_channel' => 'ONSITE',
        'payer_phone' => '670000000',
        'status' => 'successful',
        'payment_type' => 'installment',
        'admin_notes' => 'Paid at front desk.',
    ]);

    $response->assertSessionHas('success');

    $payment = Payment::query()->latest('id')->first();

    expect($payment)->not->toBeNull();
    expect($payment->application_id)->toBe($application->id);
    expect((float) $payment->amount)->toBe(30000.0);
    expect($payment->manual_entry)->toBeTrue();
    expect($payment->payment_channel)->toBe('ONSITE');
    expect($payment->status)->toBe('successful');
    expect($payment->receipt_number)->not->toBeNull();

    Notification::assertSentTo($user, PaymentReceiptNotification::class);
});

it('automatically classifies partial manual payment as installment', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();
    $program = Program::factory()->create([
        'price' => 20000,
        'max_installments' => 2,
    ]);

    $application = Application::factory()->create([
        'user_id' => $user->id,
        'program_id' => $program->id,
        'status' => 'accepted',
        'phone' => '670000111',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.payments.manual-store'), [
        'application_id' => $application->id,
        'amount' => 10000,
        'provider' => 'CASH',
        'payment_channel' => 'ONSITE',
        'payer_phone' => '670000111',
        'status' => 'successful',
        'payment_type' => 'full',
        'admin_notes' => 'Partial onsite payment.',
    ]);

    $response->assertSessionHas('success');

    $payment = Payment::query()->latest('id')->first();

    expect($payment)->not->toBeNull();
    expect((float) $payment->amount)->toBe(10000.0);
    expect($payment->payment_type)->toBe('installment');
    expect($payment->installment_number)->toBe(1);
    expect($payment->total_installments)->toBe(2);

    $paidAmount = (float) Payment::query()
        ->where('application_id', $application->id)
        ->where('status', 'successful')
        ->sum('amount');

    expect($paidAmount)->toBe(10000.0);
    expect(20000.0 - $paidAmount)->toBe(10000.0);
});

it('allows admin to edit payment amount and status', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();
    $program = Program::factory()->create(['price' => 100000]);
    $application = Application::factory()->create([
        'user_id' => $user->id,
        'program_id' => $program->id,
        'status' => 'accepted',
    ]);

    $payment = Payment::factory()->create([
        'application_id' => $application->id,
        'user_id' => $user->id,
        'program_id' => $program->id,
        'status' => 'pending',
        'manual_entry' => false,
        'payment_channel' => 'ONLINE',
        'receipt_number' => null,
        'paid_at' => null,
    ]);

    $response = $this->actingAs($admin)->patch(route('admin.payments.update', $payment), [
        'reference' => $payment->reference,
        'receipt_number' => '',
        'payer_phone' => '677000000',
        'provider' => 'MTN',
        'payment_channel' => 'ONLINE',
        'amount' => 45000,
        'payment_type' => 'installment',
        'installment_number' => 1,
        'total_installments' => 2,
        'status' => 'successful',
        'manual_entry' => false,
        'failure_reason' => '',
        'admin_notes' => 'Validated manually.',
    ]);

    $response->assertSessionHas('success');

    $payment->refresh();

    expect((float) $payment->amount)->toBe(45000.0);
    expect($payment->status)->toBe('successful');
    expect($payment->receipt_number)->not->toBeNull();
    expect($payment->paid_at)->not->toBeNull();
    expect($payment->admin_notes)->toBe('Validated manually.');
});
