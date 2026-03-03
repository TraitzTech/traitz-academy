<?php

use App\Models\AiForgeEvent;
use App\Models\AiForgeOrder;
use App\Models\AiForgeSwag;
use App\Notifications\AiForgeSwagOrderReceipt;
use App\Support\Payments\Contracts\PaymentGateway;
use App\Support\Payments\DTOs\PaymentGatewayResult;
use Illuminate\Support\Facades\Notification;

function createSwagPaymentGateway(bool $successful = true, ?string $message = null): void
{
    app()->bind(PaymentGateway::class, fn () => new class($successful, $message) implements PaymentGateway
    {
        public function __construct(
            private bool $successful,
            private ?string $message,
        ) {}

        public function collect(array $payload): PaymentGatewayResult
        {
            return new PaymentGatewayResult(
                operationSuccessful: $this->successful,
                transactionSuccessful: $this->successful,
                transactionId: $this->successful ? 'trx-swag-test-123' : null,
                message: $this->message ?? ($this->successful ? 'ok' : 'Payment failed at provider.'),
                rawResponse: ['ok' => $this->successful],
            );
        }
    });
}

function seedSwagCart(AiForgeSwag $swag, int $quantity = 1, ?string $variation = null): void
{
    $cartKey = $swag->id.'-'.($variation ?? 'default');
    session(['ai_forge_cart' => [
        $cartKey => [
            'swag_id' => $swag->id,
            'variation' => $variation,
            'quantity' => $quantity,
        ],
    ]]);
}

it('processes a successful swag payment and sends receipt email', function () {
    Notification::fake();

    $event = AiForgeEvent::factory()->create(['is_active' => true, 'swag_store_active' => true]);
    $swag = AiForgeSwag::factory()->create([
        'ai_forge_event_id' => $event->id,
        'price' => 5000,
        'stock_quantity' => 20,
        'is_active' => true,
    ]);

    createSwagPaymentGateway(successful: true);
    seedSwagCart($swag, quantity: 2);

    $response = $this->post(route('ai-forge.checkout.process'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '670000000',
        'payer_phone' => '670000000',
        'provider' => 'MTN',
    ]);

    $order = AiForgeOrder::first();

    expect($order)->not->toBeNull();
    expect($order->payment_status)->toBe('paid');
    expect($order->status)->toBe('confirmed');
    expect((float) $order->subtotal)->toBe(10000.0);
    expect($order->receipt_number)->not->toBeNull();
    expect($order->transaction_id)->toBe('trx-swag-test-123');
    expect($order->paid_at)->not->toBeNull();
    expect($order->items)->toHaveCount(1);
    expect($order->items->first()->quantity)->toBe(2);

    // Verify stock was decremented
    $swag->refresh();
    expect($swag->stock_quantity)->toBe(18);
    expect($swag->sold_count)->toBe(2);

    // Verify cart was cleared
    expect(session('ai_forge_cart'))->toBeNull();

    // Verify receipt email was sent
    Notification::assertSentTo(
        Notification::route('mail', 'john@example.com'),
        AiForgeSwagOrderReceipt::class,
    );

    $response->assertRedirect(route('ai-forge.order.confirmation', $order));
});

it('shows error message when payment fails', function () {
    $event = AiForgeEvent::factory()->create(['is_active' => true, 'swag_store_active' => true]);
    $swag = AiForgeSwag::factory()->create([
        'ai_forge_event_id' => $event->id,
        'price' => 5000,
        'stock_quantity' => 20,
        'is_active' => true,
    ]);

    createSwagPaymentGateway(successful: false, message: 'Insufficient balance');
    seedSwagCart($swag);

    $response = $this->post(route('ai-forge.checkout.process'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'phone' => '670000000',
        'payer_phone' => '670000000',
        'provider' => 'MTN',
    ]);

    $order = AiForgeOrder::first();

    expect($order)->not->toBeNull();
    expect($order->payment_status)->toBe('failed');
    expect($order->failure_reason)->toBe('Insufficient balance');

    // Stock should not be decremented
    $swag->refresh();
    expect($swag->stock_quantity)->toBe(20);

    // Cart should still exist
    expect(session('ai_forge_cart'))->not->toBeEmpty();

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

it('sanitizes payer phone number by stripping non-digits and country code', function () {
    $captured = new \stdClass;
    $captured->payloads = [];

    app()->bind(PaymentGateway::class, function () use ($captured) {
        return new class($captured) implements PaymentGateway
        {
            public function __construct(private \stdClass $captured) {}

            public function collect(array $payload): PaymentGatewayResult
            {
                $this->captured->payloads[] = $payload;

                return new PaymentGatewayResult(
                    operationSuccessful: true,
                    transactionSuccessful: true,
                    transactionId: 'trx-phone-test',
                    message: 'ok',
                    rawResponse: ['ok' => true],
                );
            }
        };
    });

    $event = AiForgeEvent::factory()->create(['is_active' => true, 'swag_store_active' => true]);
    $swag = AiForgeSwag::factory()->create([
        'ai_forge_event_id' => $event->id,
        'price' => 5000,
        'stock_quantity' => 20,
        'is_active' => true,
    ]);

    seedSwagCart($swag);

    $this->post(route('ai-forge.checkout.process'), [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'phone' => '670000000',
        'payer_phone' => '+237 670 000 000',
        'provider' => 'MTN',
    ]);

    expect($captured->payloads)->toHaveCount(1);
    expect($captured->payloads[0]['payer'])->toBe('670000000');
});

it('handles payment gateway exception gracefully', function () {
    app()->bind(PaymentGateway::class, fn () => new class implements PaymentGateway
    {
        public function collect(array $payload): PaymentGatewayResult
        {
            throw new \RuntimeException('MeSomb credentials are not configured.');
        }
    });

    $event = AiForgeEvent::factory()->create(['is_active' => true, 'swag_store_active' => true]);
    $swag = AiForgeSwag::factory()->create([
        'ai_forge_event_id' => $event->id,
        'price' => 5000,
        'stock_quantity' => 20,
        'is_active' => true,
    ]);

    seedSwagCart($swag);

    $response = $this->post(route('ai-forge.checkout.process'), [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'phone' => '670000000',
        'payer_phone' => '670000000',
        'provider' => 'MTN',
    ]);

    $order = AiForgeOrder::first();
    expect($order->payment_status)->toBe('failed');
    expect($order->failure_reason)->toContain('credentials');

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

it('redirects with error when cart is empty', function () {
    AiForgeEvent::factory()->create(['is_active' => true, 'swag_store_active' => true]);
    createSwagPaymentGateway();

    $response = $this->post(route('ai-forge.checkout.process'), [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'phone' => '670000000',
        'payer_phone' => '670000000',
        'provider' => 'MTN',
    ]);

    $response->assertRedirect(route('ai-forge.swags.index'));
    $response->assertSessionHas('error', 'Your cart is empty.');
    expect(AiForgeOrder::count())->toBe(0);
});

it('validates required checkout fields', function () {
    $response = $this->post(route('ai-forge.checkout.process'), []);

    $response->assertSessionHasErrors(['first_name', 'last_name', 'email', 'phone', 'payer_phone', 'provider']);
});

it('calculates surcharge correctly on swag payment', function () {
    $event = AiForgeEvent::factory()->create(['is_active' => true, 'swag_store_active' => true]);
    $swag = AiForgeSwag::factory()->create([
        'ai_forge_event_id' => $event->id,
        'price' => 10000,
        'stock_quantity' => 10,
        'is_active' => true,
    ]);

    createSwagPaymentGateway();
    seedSwagCart($swag, quantity: 1);

    $this->post(route('ai-forge.checkout.process'), [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'phone' => '670000000',
        'payer_phone' => '670000000',
        'provider' => 'MTN',
    ]);

    $order = AiForgeOrder::first();

    expect($order)->not->toBeNull();
    expect((float) $order->subtotal)->toBe(10000.0);
    expect((float) $order->surcharge_percentage)->toBe(2.0);
    expect((float) $order->surcharge_amount)->toBe(200.0);
    expect((float) $order->total_amount)->toBe(10200.0);
});
