<?php

namespace App\Http\Controllers;

use App\Models\AiForgeEvent;
use App\Models\AiForgeOrder;
use App\Models\AiForgeOrderItem;
use App\Models\AiForgeSwag;
use App\Models\SiteSetting;
use App\Notifications\AiForgeSwagOrderReceipt;
use App\Support\Payments\Contracts\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AiForgeCartController extends Controller
{
    public function __construct(private PaymentGateway $paymentGateway) {}

    public function index(Request $request): Response
    {
        $cart = session('ai_forge_cart', []);
        $cartItems = $this->hydrateCart($cart);
        $cartTotal = $this->calculateCartTotal($cartItems);
        $surchargePercentage = $this->getOnlineSurchargePercentage();
        $surchargeAmount = round(($cartTotal * $surchargePercentage) / 100, 2);

        return Inertia::render('AiForge/Cart', [
            'cartItems' => $cartItems,
            'cartTotal' => $cartTotal,
            'surchargePercentage' => $surchargePercentage,
            'surchargeAmount' => $surchargeAmount,
            'grandTotal' => round($cartTotal + $surchargeAmount, 2),
        ]);
    }

    public function addToCart(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'swag_id' => ['required', 'integer', 'exists:ai_forge_swags,id'],
            'variation' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $swag = AiForgeSwag::findOrFail($validated['swag_id']);

        if (! $swag->is_active || $swag->stock_quantity < $validated['quantity']) {
            return back()->with('error', 'This item is not available in the requested quantity.');
        }

        $cart = session('ai_forge_cart', []);
        $cartKey = $validated['swag_id'].'-'.($validated['variation'] ?? 'default');

        if (isset($cart[$cartKey])) {
            $newQuantity = $cart[$cartKey]['quantity'] + $validated['quantity'];
            if ($newQuantity > $swag->stock_quantity || $newQuantity > 10) {
                return back()->with('error', 'Cannot add more of this item.');
            }
            $cart[$cartKey]['quantity'] = $newQuantity;
        } else {
            $cart[$cartKey] = [
                'swag_id' => $validated['swag_id'],
                'variation' => $validated['variation'] ?? null,
                'quantity' => $validated['quantity'],
            ];
        }

        session(['ai_forge_cart' => $cart]);

        return back()->with('success', "{$swag->name} added to cart!");
    }

    public function updateCart(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cart_key' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:0', 'max:10'],
        ]);

        $cart = session('ai_forge_cart', []);

        if ($validated['quantity'] === 0) {
            unset($cart[$validated['cart_key']]);
        } elseif (isset($cart[$validated['cart_key']])) {
            $swag = AiForgeSwag::find($cart[$validated['cart_key']]['swag_id']);
            if ($swag && $validated['quantity'] <= $swag->stock_quantity) {
                $cart[$validated['cart_key']]['quantity'] = $validated['quantity'];
            } else {
                return back()->with('error', 'Requested quantity not available.');
            }
        }

        session(['ai_forge_cart' => $cart]);

        return back()->with('success', 'Cart updated.');
    }

    public function removeFromCart(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cart_key' => ['required', 'string'],
        ]);

        $cart = session('ai_forge_cart', []);
        unset($cart[$validated['cart_key']]);
        session(['ai_forge_cart' => $cart]);

        return back()->with('success', 'Item removed from cart.');
    }

    public function checkout(Request $request): Response|RedirectResponse
    {
        $cart = session('ai_forge_cart', []);
        if (empty($cart)) {
            return redirect()->route('ai-forge.swags.index')->with('error', 'Your cart is empty.');
        }

        $cartItems = $this->hydrateCart($cart);
        $cartTotal = $this->calculateCartTotal($cartItems);
        $surchargePercentage = $this->getOnlineSurchargePercentage();
        $surchargeAmount = round(($cartTotal * $surchargePercentage) / 100, 2);

        $event = AiForgeEvent::query()->where('is_active', true)->first();

        $user = auth()->user();
        $nameParts = $user ? explode(' ', $user->name ?? '', 2) : [];

        return Inertia::render('AiForge/Checkout', [
            'event' => $event ? ['id' => $event->id, 'title' => $event->title] : null,
            'cartItems' => $cartItems,
            'cartTotal' => $cartTotal,
            'surchargePercentage' => $surchargePercentage,
            'surchargeAmount' => $surchargeAmount,
            'grandTotal' => round($cartTotal + $surchargeAmount, 2),
            'user' => $user ? [
                'first_name' => $nameParts[0] ?? '',
                'last_name' => $nameParts[1] ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->phone ?? '',
            ] : null,
        ]);
    }

    public function processPayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:8', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'payer_phone' => ['required', 'string', 'min:8', 'max:20'],
            'provider' => ['required', 'in:MTN,ORANGE'],
        ]);

        $cart = session('ai_forge_cart', []);
        if (empty($cart)) {
            return redirect()->route('ai-forge.swags.index')->with('error', 'Your cart is empty.');
        }

        $event = AiForgeEvent::query()->where('is_active', true)->firstOrFail();

        // Sanitize payer phone: strip all non-digit characters, then remove leading country code (237)
        $sanitizedPayerPhone = preg_replace('/\D/', '', $validated['payer_phone']);
        if (str_starts_with($sanitizedPayerPhone, '237') && strlen($sanitizedPayerPhone) > 9) {
            $sanitizedPayerPhone = substr($sanitizedPayerPhone, 3);
        }

        $order = DB::transaction(function () use ($cart, $validated, $event, $sanitizedPayerPhone) {
            $cartItems = $this->hydrateCart($cart);
            $subtotal = $this->calculateCartTotal($cartItems);
            $surchargePercentage = $this->getOnlineSurchargePercentage();
            $surchargeAmount = round(($subtotal * $surchargePercentage) / 100, 2);
            $totalAmount = round($subtotal + $surchargeAmount, 2);

            $order = AiForgeOrder::create([
                'ai_forge_event_id' => $event->id,
                'user_id' => auth()->id(),
                'order_number' => 'AIF-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6)),
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'country' => $validated['country'] ?? null,
                'subtotal' => $subtotal,
                'surcharge_amount' => $surchargeAmount,
                'surcharge_percentage' => $surchargePercentage,
                'total_amount' => $totalAmount,
                'currency' => (string) config('services.mesomb.currency', 'XAF'),
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_provider' => $validated['provider'],
                'payer_phone' => $sanitizedPayerPhone,
            ]);

            foreach ($cartItems as $item) {
                AiForgeOrderItem::create([
                    'ai_forge_order_id' => $order->id,
                    'ai_forge_swag_id' => $item['swag']['id'],
                    'variation' => $item['variation'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['swag']['price'],
                    'total_price' => $item['swag']['price'] * $item['quantity'],
                ]);
            }

            return $order;
        });

        try {
            $amount = (int) round((float) $order->total_amount);

            Log::info('AI Forge swag payment initiated', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount' => $amount,
                'payer_phone' => $sanitizedPayerPhone,
                'provider' => $validated['provider'],
            ]);

            $gatewayResponse = $this->paymentGateway->collect([
                'payer' => $sanitizedPayerPhone,
                'amount' => $amount,
                'service' => $validated['provider'],
                'country' => (string) config('services.mesomb.country', 'CM'),
                'currency' => (string) $order->currency,
                'customer' => [
                    'email' => $validated['email'],
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'country' => (string) config('services.mesomb.country', 'CM'),
                ],
                'products' => $order->items->map(fn (AiForgeOrderItem $item) => [
                    'id' => (string) $item->ai_forge_swag_id,
                    'name' => $item->swag->name ?? 'Swag',
                    'category' => 'swag',
                    'quantity' => $item->quantity,
                    'amount' => (int) round((float) $item->total_price),
                ])->toArray(),
            ]);

            if ($gatewayResponse->isSuccessful()) {
                $order->update([
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'transaction_id' => $gatewayResponse->transactionId,
                    'receipt_number' => 'AIF-RCT-'.now()->format('Ymd').'-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
                    'paid_at' => now(),
                    'raw_response' => $gatewayResponse->rawResponse,
                ]);

                // Decrement stock and increment sold count
                foreach ($order->items as $item) {
                    AiForgeSwag::where('id', $item->ai_forge_swag_id)
                        ->decrement('stock_quantity', $item->quantity);
                    AiForgeSwag::where('id', $item->ai_forge_swag_id)
                        ->increment('sold_count', $item->quantity);
                }

                // Update event stats
                $stats = $event->stats ?? [];
                $stats['total_swags_sold'] = (int) ($stats['total_swags_sold'] ?? 0) + $order->items->sum('quantity');
                $event->update(['stats' => $stats]);

                // Send receipt notification to customer email
                try {
                    $notifiable = new AnonymousNotifiable;
                    $notifiable->route('mail', $order->email)
                        ->notify(new AiForgeSwagOrderReceipt($order->load('items.swag')));
                } catch (\Throwable $e) {
                    Log::error('Failed to send AI Forge swag order receipt email', [
                        'order_id' => $order->id,
                        'email' => $order->email,
                        'message' => $e->getMessage(),
                    ]);
                }

                // Clear cart
                session()->forget('ai_forge_cart');

                return redirect()->route('ai-forge.order.confirmation', $order)
                    ->with('success', 'Payment successful! Your receipt has been emailed to '.$order->email.'.');
            }

            Log::warning('AI Forge swag payment not successful', [
                'order_id' => $order->id,
                'operation_successful' => $gatewayResponse->operationSuccessful,
                'transaction_successful' => $gatewayResponse->transactionSuccessful,
                'message' => $gatewayResponse->message,
                'transaction_id' => $gatewayResponse->transactionId,
                'raw_response' => $gatewayResponse->rawResponse,
            ]);

            $order->update([
                'payment_status' => 'failed',
                'transaction_id' => $gatewayResponse->transactionId,
                'failure_reason' => $gatewayResponse->message,
                'raw_response' => $gatewayResponse->rawResponse,
            ]);

            $errorMessage = $gatewayResponse->message ?: 'Payment was not completed by the provider.';

            return back()->with('error', 'Payment failed: '.$errorMessage.' Please check your phone number and try again.');
        } catch (\Throwable $exception) {
            Log::error('AI Forge swag payment exception', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'exception_class' => get_class($exception),
                'message' => $exception->getMessage(),
                'payer_phone' => $sanitizedPayerPhone,
                'provider' => $validated['provider'],
                'amount' => (float) $order->total_amount,
            ]);

            $order->update([
                'payment_status' => 'failed',
                'failure_reason' => $exception->getMessage(),
            ]);

            $userMessage = match (true) {
                str_contains($exception->getMessage(), 'credentials') => 'Payment service configuration error. Please contact support.',
                str_contains($exception->getMessage(), 'Service') => 'The selected payment provider is temporarily unavailable. Please try the other provider.',
                str_contains($exception->getMessage(), 'insufficient') => 'Insufficient balance on your mobile money account.',
                str_contains($exception->getMessage(), 'timeout') => 'Payment request timed out. Please try again.',
                default => $exception->getMessage() ?: 'Payment could not be completed. Please verify your phone number and try again.',
            };

            return back()->with('error', $userMessage);
        }
    }

    public function confirmation(AiForgeOrder $order): Response
    {
        if (auth()->check() && auth()->id() !== $order->user_id && ! auth()->user()->canAccessAdminPanel()) {
            abort(403);
        }

        $order->load('items.swag');

        return Inertia::render('AiForge/OrderConfirmation', [
            'order' => $order,
        ]);
    }

    /**
     * @return array<int, array{cart_key: string, swag: AiForgeSwag, variation: string|null, quantity: int}>
     */
    private function hydrateCart(array $cart): array
    {
        $items = [];
        $swagIds = collect($cart)->pluck('swag_id')->unique()->toArray();
        $swags = AiForgeSwag::whereIn('id', $swagIds)->get()->keyBy('id');

        foreach ($cart as $key => $item) {
            $swag = $swags->get($item['swag_id']);
            if ($swag && $swag->is_active) {
                $items[] = [
                    'cart_key' => $key,
                    'swag' => $swag->toArray(),
                    'variation' => $item['variation'],
                    'quantity' => $item['quantity'],
                    'total' => $swag->price * $item['quantity'],
                ];
            }
        }

        return $items;
    }

    private function calculateCartTotal(array $cartItems): float
    {
        return collect($cartItems)->sum(fn ($item) => $item['swag']['price'] * $item['quantity']);
    }

    private function getOnlineSurchargePercentage(): float
    {
        $configuredPercentage = (float) SiteSetting::get('online_payment_surcharge_percentage', 2);

        return max(0, min(100, round($configuredPercentage, 2)));
    }
}
