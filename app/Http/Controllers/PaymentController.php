<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Payment;
use App\Notifications\PaymentReceiptNotification;
use App\Support\Payments\Contracts\PaymentGateway;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PaymentController extends Controller
{
    public function __construct(private PaymentGateway $paymentGateway) {}

    public function checkout(Application $application): Response|RedirectResponse
    {
        if (! $this->canManageApplicationPayment($application)) {
            return redirect()->route('dashboard')->with('error', 'You cannot pay for this application.');
        }

        $application->load(['program', 'payments' => function ($query) {
            $query->latest();
        }]);

        $summary = $this->buildPaymentSummary($application);
        if (! $summary['can_pay']) {
            return redirect()->route('dashboard')->with('info', 'This program does not require payment or is already fully paid.');
        }

        return Inertia::render('Payments/Checkout', [
            'application' => $application,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request, Application $application): RedirectResponse
    {
        if (! $this->canManageApplicationPayment($application)) {
            return back()->with('error', 'You cannot pay for this application.');
        }

        $validated = $request->validate([
            'payer_phone' => ['required', 'string', 'min:8', 'max:20'],
            'provider' => ['required', 'in:MTN,ORANGE'],
            'payment_mode' => ['required', 'in:full,installment'],
        ]);

        $application->load('program');

        $payment = DB::transaction(function () use ($application, $validated) {
            $lockedApplication = Application::query()
                ->whereKey($application->id)
                ->with('program')
                ->lockForUpdate()
                ->firstOrFail();

            $summary = $this->buildPaymentSummary($lockedApplication);

            if (! $summary['can_pay']) {
                abort(422, 'This application is already fully paid.');
            }

            $isInstallmentPayment = $validated['payment_mode'] === 'installment';

            $amount = $isInstallmentPayment
                ? min((float) $summary['installment_amount'], (float) $summary['remaining_amount'])
                : (float) $summary['remaining_amount'];

            return Payment::create([
                'application_id' => $lockedApplication->id,
                'user_id' => (int) auth()->id(),
                'program_id' => $lockedApplication->program_id,
                'reference' => $this->buildReference(),
                'payer_phone' => $validated['payer_phone'],
                'provider' => $validated['provider'],
                'amount' => $amount,
                'currency' => (string) config('services.mesomb.currency', 'XAF'),
                'payment_type' => $validated['payment_mode'],
                'installment_number' => (int) $summary['next_installment_number'],
                'total_installments' => (int) $summary['max_installments'],
                'status' => 'pending',
                'receipt_number' => null,
            ]);
        });

        try {
            $gatewayResponse = $this->paymentGateway->collect([
                'payer' => preg_replace('/\s+/', '', $payment->payer_phone),
                'amount' => (float) $payment->amount,
                'service' => $payment->provider,
                'country' => (string) config('services.mesomb.country', 'CM'),
                'currency' => (string) $payment->currency,
                'customer' => [
                    'email' => (string) auth()->user()->email,
                    'first_name' => (string) $application->first_name,
                    'last_name' => (string) $application->last_name,
                    'country' => (string) config('services.mesomb.country', 'CM'),
                ],
                'products' => [[
                    'id' => (string) $application->program->id,
                    'name' => (string) $application->program->title,
                    'category' => (string) $application->program->category,
                    'quantity' => 1,
                    'amount' => (float) $payment->amount,
                ]],
            ]);

            if ($gatewayResponse->isSuccessful()) {
                $payment->update([
                    'status' => 'successful',
                    'mesomb_transaction_id' => $gatewayResponse->transactionId,
                    'receipt_number' => $this->buildReceiptNumber($payment),
                    'paid_at' => now(),
                    'failure_reason' => null,
                    'raw_response' => $gatewayResponse->rawResponse,
                ]);

                auth()->user()->notify(new PaymentReceiptNotification($payment->fresh(['application.program'])));

                return redirect()->route('payments.receipt', $payment)
                    ->with('success', 'Payment successful. Your receipt has been emailed.');
            }

            $payment->update([
                'status' => 'failed',
                'mesomb_transaction_id' => $gatewayResponse->transactionId,
                'failure_reason' => $gatewayResponse->message,
                'raw_response' => $gatewayResponse->rawResponse,
            ]);

            return back()->with('error', $gatewayResponse->message ?? 'Payment failed. Please try again.');
        } catch (\Throwable $exception) {
            Log::warning('Payment gateway collect failed', [
                'payment_id' => $payment->id,
                'application_id' => $application->id,
                'message' => $exception->getMessage(),
            ]);

            $payment->update([
                'status' => 'failed',
                'failure_reason' => $exception->getMessage(),
            ]);

            return back()->with('error', $exception->getMessage() ?: 'Payment could not be completed right now. Please try again.');
        }
    }

    public function receipt(Payment $payment): Response
    {
        $this->ensureCanViewPaymentReceipt($payment);

        $payment->load(['application.program', 'user', 'recordedBy', 'updatedBy']);

        return Inertia::render('Payments/Receipt', [
            'payment' => $payment,
        ]);
    }

    public function downloadReceiptPdf(Payment $payment): SymfonyResponse
    {
        $this->ensureCanViewPaymentReceipt($payment);

        $fileName = 'receipt-'.($payment->receipt_number ?: $payment->id).'.pdf';

        return $this->buildReceiptPdfDocument($payment)->download($fileName);
    }

    public function printReceiptPdf(Payment $payment): SymfonyResponse
    {
        $this->ensureCanViewPaymentReceipt($payment);

        $fileName = 'receipt-'.($payment->receipt_number ?: $payment->id).'.pdf';

        return $this->buildReceiptPdfDocument($payment)->stream($fileName);
    }

    private function buildReceiptPdfDocument(Payment $payment): DomPdf
    {
        $payment->load(['application.program', 'user', 'recordedBy', 'updatedBy']);

        $receiptUrl = route('payments.receipt', $payment);

        $renderer = new ImageRenderer(
            new RendererStyle(180),
            new SvgImageBackEnd
        );

        $qrCodeSvg = (new Writer($renderer))->writeString($receiptUrl);
        $qrCodeDataUri = 'data:image/svg+xml;base64,'.base64_encode($qrCodeSvg);

        return Pdf::loadView('payments.receipt-pdf', [
            'payment' => $payment,
            'receiptUrl' => $receiptUrl,
            'qrCodeDataUri' => $qrCodeDataUri,
        ])->setPaper('a4');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPaymentSummary(Application $application): array
    {
        $programPrice = (float) ($application->program?->price ?? 0);
        $maxInstallments = max(1, (int) ($application->program?->max_installments ?? 1));
        $paidAmount = (float) $application->payments()->where('status', 'successful')->sum('amount');
        $successfulInstallments = $application->payments()->where('status', 'successful')->count();
        $remainingAmount = max(0, round($programPrice - $paidAmount, 2));
        $installmentAmount = $maxInstallments > 0 ? round($programPrice / $maxInstallments, 2) : $programPrice;

        return [
            'program_price' => $programPrice,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'max_installments' => $maxInstallments,
            'installment_amount' => $installmentAmount,
            'completed_installments' => $successfulInstallments,
            'next_installment_number' => min($maxInstallments, $successfulInstallments + 1),
            'can_pay' => $application->status === 'accepted' && $programPrice > 0 && $remainingAmount > 0,
        ];
    }

    private function canManageApplicationPayment(Application $application): bool
    {
        return $application->user_id === auth()->id() && $application->status === 'accepted';
    }

    private function ensureCanViewPaymentReceipt(Payment $payment): void
    {
        $isAuthorized = auth()->id() === $payment->user_id || auth()->user()?->canAccessAdminPanel();
        abort_unless($isAuthorized, 403);
    }

    private function buildReference(): string
    {
        return 'TRZ-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
    }

    private function buildReceiptNumber(Payment $payment): string
    {
        return 'RCT-'.now()->format('Ymd').'-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }
}
