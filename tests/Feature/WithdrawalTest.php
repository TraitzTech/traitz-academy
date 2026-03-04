<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Models\Withdrawal;
use App\Support\Payments\MesombDepositService;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

// --- Access Control ---

it('prevents non-executives from accessing withdrawals page', function () {
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);
    $regularUser = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($coordinator)
        ->get(route('admin.withdrawals.index'))
        ->assertForbidden();

    $this->actingAs($regularUser)
        ->get(route('admin.withdrawals.index'))
        ->assertForbidden();
});

it('allows CEO and CTO to access withdrawals page', function () {
    $ceo = User::factory()->create(['role' => User::ROLE_CEO]);
    $cto = User::factory()->create(['role' => User::ROLE_CTO]);

    $this->actingAs($ceo)
        ->get(route('admin.withdrawals.index'))
        ->assertSuccessful();

    $this->actingAs($cto)
        ->get(route('admin.withdrawals.index'))
        ->assertSuccessful();
});

it('returns correct Inertia page with expected props', function () {
    $ceo = User::factory()->create(['role' => User::ROLE_CEO]);

    $this->actingAs($ceo)
        ->get(route('admin.withdrawals.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Withdrawals/Index')
            ->has('withdrawals')
            ->has('stats')
            ->has('hasPin')
            ->has('services')
        );
});

// --- PIN Setup ---

it('allows executive to set a withdrawal PIN', function () {
    $ceo = User::factory()->create(['role' => User::ROLE_CEO]);

    $this->actingAs($ceo)
        ->post(route('admin.withdrawals.set-pin'), [
            'pin' => '1234',
            'pin_confirmation' => '1234',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $ceo->refresh();
    expect($ceo->hasWithdrawalPin())->toBeTrue();
    expect(Hash::check('1234', $ceo->withdrawal_pin))->toBeTrue();
});

it('requires PIN to be exactly 4 digits', function () {
    $ceo = User::factory()->create(['role' => User::ROLE_CEO]);

    $this->actingAs($ceo)
        ->post(route('admin.withdrawals.set-pin'), [
            'pin' => '123',
            'pin_confirmation' => '123',
        ])
        ->assertSessionHasErrors('pin');

    $this->actingAs($ceo)
        ->post(route('admin.withdrawals.set-pin'), [
            'pin' => 'abcd',
            'pin_confirmation' => 'abcd',
        ])
        ->assertSessionHasErrors('pin');
});

it('requires PIN confirmation to match', function () {
    $ceo = User::factory()->create(['role' => User::ROLE_CEO]);

    $this->actingAs($ceo)
        ->post(route('admin.withdrawals.set-pin'), [
            'pin' => '1234',
            'pin_confirmation' => '5678',
        ])
        ->assertSessionHasErrors('pin_confirmation');
});

it('requires current PIN when changing existing PIN', function () {
    $ceo = User::factory()->create([
        'role' => User::ROLE_CEO,
        'withdrawal_pin' => Hash::make('1234'),
    ]);

    // Without current PIN
    $this->actingAs($ceo)
        ->post(route('admin.withdrawals.set-pin'), [
            'pin' => '5678',
            'pin_confirmation' => '5678',
        ])
        ->assertSessionHasErrors('current_pin');

    // With wrong current PIN
    $this->actingAs($ceo)
        ->post(route('admin.withdrawals.set-pin'), [
            'current_pin' => '0000',
            'pin' => '5678',
            'pin_confirmation' => '5678',
        ])
        ->assertSessionHasErrors('current_pin');

    // With correct current PIN
    $this->actingAs($ceo)
        ->post(route('admin.withdrawals.set-pin'), [
            'current_pin' => '1234',
            'pin' => '5678',
            'pin_confirmation' => '5678',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $ceo->refresh();
    expect(Hash::check('5678', $ceo->withdrawal_pin))->toBeTrue();
});

it('prevents non-executive from setting PIN', function () {
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);

    $this->actingAs($coordinator)
        ->post(route('admin.withdrawals.set-pin'), [
            'pin' => '1234',
            'pin_confirmation' => '1234',
        ])
        ->assertForbidden();
});

// --- Withdrawal ---

it('prevents withdrawal without a PIN set', function () {
    $ceo = User::factory()->create(['role' => User::ROLE_CEO]);

    $this->actingAs($ceo)
        ->post(route('admin.withdrawals.store'), [
            'amount' => 5000,
            'service' => 'MTN',
            'receiver' => '670000000',
            'receiver_name' => 'John Doe',
            'pin' => '1234',
        ])
        ->assertForbidden();
});

it('rejects withdrawal with incorrect PIN', function () {
    $ceo = User::factory()->create([
        'role' => User::ROLE_CEO,
        'withdrawal_pin' => Hash::make('1234'),
    ]);

    $this->actingAs($ceo)
        ->post(route('admin.withdrawals.store'), [
            'amount' => 5000,
            'service' => 'MTN',
            'receiver' => '670000000',
            'receiver_name' => 'John Doe',
            'pin' => '0000',
        ])
        ->assertSessionHasErrors('pin');
});

it('validates withdrawal form fields', function (string $field, mixed $value, string $errorField) {
    $ceo = User::factory()->create([
        'role' => User::ROLE_CEO,
        'withdrawal_pin' => Hash::make('1234'),
    ]);

    $basePayload = [
        'amount' => 5000,
        'service' => 'MTN',
        'receiver' => '670000000',
        'receiver_name' => 'John Doe',
        'pin' => '1234',
    ];

    $this->actingAs($ceo)
        ->post(route('admin.withdrawals.store'), array_merge($basePayload, [$field => $value]))
        ->assertSessionHasErrors($errorField);
})->with([
    'amount too low' => ['amount', 50, 'amount'],
    'missing amount' => ['amount', null, 'amount'],
    'invalid service' => ['service', 'VODAFONE', 'service'],
    'missing service' => ['service', '', 'service'],
    'invalid phone format' => ['receiver', '12345', 'receiver'],
    'missing phone' => ['receiver', '', 'receiver'],
    'missing receiver name' => ['receiver_name', '', 'receiver_name'],
    'invalid pin format' => ['pin', 'ab', 'pin'],
]);

it('creates a withdrawal record on submission', function () {
    $ceo = User::factory()->create([
        'role' => User::ROLE_CEO,
        'withdrawal_pin' => Hash::make('1234'),
    ]);

    // Mock the MesombDepositService to avoid actual API calls
    $this->mock(MesombDepositService::class, function ($mock) {
        $transaction = new \MeSomb\Model\TransactionResponse([
            'success' => true,
            'message' => 'Deposit successful',
            'redirect' => null,
            'transaction' => [
                'id' => 'txn_123',
                'pk' => 'txn_123',
                'status' => 'SUCCESS',
                'type' => 'DEPOSIT',
                'amount' => 5000,
                'fees' => 0,
                'b_party' => '670000000',
                'message' => 'success',
                'service' => 'MTN',
                'reference' => 'ref_123',
                'ts' => now()->toISOString(),
                'direction' => 1,
                'country' => 'CM',
                'currency' => 'XAF',
            ],
            'reference' => 'ref_123',
            'status' => 'SUCCESS',
        ]);

        $mock->shouldReceive('deposit')->once()->andReturn($transaction);
    });

    $this->actingAs($ceo)
        ->post(route('admin.withdrawals.store'), [
            'amount' => 5000,
            'service' => 'MTN',
            'receiver' => '670000000',
            'receiver_name' => 'John Doe',
            'pin' => '1234',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Withdrawal::count())->toBe(1);
    $withdrawal = Withdrawal::first();
    expect($withdrawal->amount)->toBe(5000);
    expect($withdrawal->service)->toBe('MTN');
    expect($withdrawal->receiver)->toBe('670000000');
    expect($withdrawal->receiver_name)->toBe('John Doe');
    expect($withdrawal->user_id)->toBe($ceo->id);
    expect($withdrawal->status)->toBe(Withdrawal::STATUS_SUCCESS);
});

it('records failed withdrawal when API fails', function () {
    $ceo = User::factory()->create([
        'role' => User::ROLE_CEO,
        'withdrawal_pin' => Hash::make('1234'),
    ]);

    $this->mock(MesombDepositService::class, function ($mock) {
        $mock->shouldReceive('deposit')->once()->andThrow(
            new \RuntimeException('Insufficient balance')
        );
    });

    $this->actingAs($ceo)
        ->post(route('admin.withdrawals.store'), [
            'amount' => 5000,
            'service' => 'MTN',
            'receiver' => '670000000',
            'receiver_name' => 'John Doe',
            'pin' => '1234',
        ])
        ->assertSessionHasErrors('withdrawal');

    expect(Withdrawal::count())->toBe(1);
    $withdrawal = Withdrawal::first();
    expect($withdrawal->status)->toBe(Withdrawal::STATUS_FAILED);
    expect($withdrawal->failure_reason)->toContain('Insufficient balance');
});

it('shows hasPin as true when user has a PIN set', function () {
    $ceo = User::factory()->create([
        'role' => User::ROLE_CEO,
        'withdrawal_pin' => Hash::make('1234'),
    ]);

    $this->actingAs($ceo)
        ->get(route('admin.withdrawals.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('hasPin', true)
        );
});

it('shows hasPin as false when user has no PIN', function () {
    $ceo = User::factory()->create(['role' => User::ROLE_CEO]);

    $this->actingAs($ceo)
        ->get(route('admin.withdrawals.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('hasPin', false)
        );
});

// --- Account Verification ---

it('prevents non-executives from verifying accounts', function () {
    $coordinator = User::factory()->create(['role' => User::ROLE_PROGRAM_COORDINATOR]);

    $this->actingAs($coordinator)
        ->postJson(route('admin.withdrawals.verify-account'), [
            'service' => 'MTN',
            'receiver' => '670000000',
        ])
        ->assertForbidden();
});

it('validates account verification request', function (string $field, mixed $value) {
    $ceo = User::factory()->create(['role' => User::ROLE_CEO]);

    $basePayload = [
        'service' => 'MTN',
        'receiver' => '670000000',
    ];

    $this->actingAs($ceo)
        ->postJson(route('admin.withdrawals.verify-account'), array_merge($basePayload, [$field => $value]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'invalid service' => ['service', 'VODAFONE'],
    'missing service' => ['service', ''],
    'invalid phone format' => ['receiver', '12345'],
    'missing phone' => ['receiver', ''],
]);

it('returns account holder name on successful verification', function () {
    $ceo = User::factory()->create(['role' => User::ROLE_CEO]);

    $this->mock(MesombDepositService::class, function ($mock) {
        $mock->shouldReceive('getContactInfo')
            ->once()
            ->with(\Mockery::on(fn ($params) => $params['provider'] === 'MTN' && $params['service_key'] === '670000000'))
            ->andReturn([
                'first_name' => 'John',
                'last_name' => 'Doe',
            ]);
    });

    $this->actingAs($ceo)
        ->postJson(route('admin.withdrawals.verify-account'), [
            'service' => 'MTN',
            'receiver' => '670000000',
        ])
        ->assertSuccessful()
        ->assertJson([
            'success' => true,
            'account_name' => 'John Doe',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
});

it('returns error when account verification fails', function () {
    $ceo = User::factory()->create(['role' => User::ROLE_CEO]);

    $this->mock(MesombDepositService::class, function ($mock) {
        $mock->shouldReceive('getContactInfo')
            ->once()
            ->andThrow(new \RuntimeException('Account lookup failed: Not found'));
    });

    $this->actingAs($ceo)
        ->postJson(route('admin.withdrawals.verify-account'), [
            'service' => 'MTN',
            'receiver' => '670000000',
        ])
        ->assertSuccessful()
        ->assertJson([
            'success' => false,
            'can_proceed_manually' => true,
        ]);
});

it('returns error when account name is empty', function () {
    $ceo = User::factory()->create(['role' => User::ROLE_CEO]);

    $this->mock(MesombDepositService::class, function ($mock) {
        $mock->shouldReceive('getContactInfo')
            ->once()
            ->andReturn([
                'first_name' => '',
                'last_name' => '',
            ]);
    });

    $this->actingAs($ceo)
        ->postJson(route('admin.withdrawals.verify-account'), [
            'service' => 'MTN',
            'receiver' => '670000000',
        ])
        ->assertSuccessful()
        ->assertJson([
            'success' => false,
            'can_proceed_manually' => true,
        ]);
});

// --- Expense Auto-Recording ---

it('auto-creates expense on successful withdrawal', function () {
    $ceo = User::factory()->create([
        'role' => User::ROLE_CEO,
        'withdrawal_pin' => Hash::make('1234'),
    ]);

    $this->mock(MesombDepositService::class, function ($mock) {
        $transaction = new \MeSomb\Model\TransactionResponse([
            'success' => true,
            'message' => 'Deposit successful',
            'redirect' => null,
            'transaction' => [
                'id' => 'txn_456',
                'pk' => 'txn_456',
                'status' => 'SUCCESS',
                'type' => 'DEPOSIT',
                'amount' => 10000,
                'fees' => 0,
                'b_party' => '670000000',
                'message' => 'success',
                'service' => 'MTN',
                'reference' => 'ref_456',
                'ts' => now()->toISOString(),
                'direction' => 1,
                'country' => 'CM',
                'currency' => 'XAF',
            ],
            'reference' => 'ref_456',
            'status' => 'SUCCESS',
        ]);

        $mock->shouldReceive('deposit')->once()->andReturn($transaction);
    });

    $this->actingAs($ceo)
        ->post(route('admin.withdrawals.store'), [
            'amount' => 10000,
            'service' => 'MTN',
            'receiver' => '670000000',
            'receiver_name' => 'Jane Doe',
            'pin' => '1234',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    // Verify expense was created
    expect(Expense::count())->toBe(1);
    $expense = Expense::first();
    expect($expense->amount)->toBe('10000.00');
    expect($expense->currency)->toBe('XAF');
    expect($expense->payment_method)->toBe('mobile_money');
    expect($expense->status)->toBe('approved');
    expect($expense->title)->toContain('Jane Doe');
    expect($expense->recorded_by)->toBe($ceo->id);

    // Verify MoMo Withdrawals category was created
    $category = ExpenseCategory::where('slug', 'momo-withdrawals')->first();
    expect($category)->not->toBeNull();
    expect($category->name)->toBe('MoMo Withdrawals');
    expect($expense->expense_category_id)->toBe($category->id);
});

it('does not create expense on failed withdrawal', function () {
    $ceo = User::factory()->create([
        'role' => User::ROLE_CEO,
        'withdrawal_pin' => Hash::make('1234'),
    ]);

    $this->mock(MesombDepositService::class, function ($mock) {
        $mock->shouldReceive('deposit')->once()->andThrow(
            new \RuntimeException('Insufficient balance')
        );
    });

    $this->actingAs($ceo)
        ->post(route('admin.withdrawals.store'), [
            'amount' => 5000,
            'service' => 'MTN',
            'receiver' => '670000000',
            'receiver_name' => 'John Doe',
            'pin' => '1234',
        ])
        ->assertSessionHasErrors('withdrawal');

    expect(Expense::count())->toBe(0);
});
