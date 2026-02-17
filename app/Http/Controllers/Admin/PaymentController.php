<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Payment;
use App\Notifications\PaymentReceiptNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Payment::query()->with([
            'program:id,title',
            'application:id,first_name,last_name,email,program_id',
            'user:id,name,email',
            'updatedBy:id,name',
        ]);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($builder) use ($search) {
                $builder->where('reference', 'like', "%{$search}%")
                    ->orWhere('receipt_number', 'like', "%{$search}%")
                    ->orWhere('payer_phone', 'like', "%{$search}%")
                    ->orWhereHas('application', function ($applicationQuery) use ($search) {
                        $applicationQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->integer('program_id'));
        }

        $payments = $query->latest()->paginate(20)->withQueryString();

        $acceptedApplications = Application::query()
            ->where('status', 'accepted')
            ->with(['program:id,title,price,max_installments', 'user:id,name,email'])
            ->withSum(['payments as successful_payments_sum_amount' => function ($paymentQuery) {
                $paymentQuery->where('status', 'successful');
            }], 'amount')
            ->withCount(['payments as successful_payments_count' => function ($paymentQuery) {
                $paymentQuery->where('status', 'successful');
            }])
            ->orderByDesc('id')
            ->get()
            ->map(function (Application $application) {
                $programPrice = (float) ($application->program?->price ?? 0);
                $paidAmount = (float) ($application->successful_payments_sum_amount ?? 0);
                $remainingAmount = max(0, round($programPrice - $paidAmount, 2));

                return [
                    'id' => $application->id,
                    'applicant_name' => trim($application->first_name.' '.$application->last_name),
                    'email' => $application->email,
                    'phone' => $application->phone,
                    'program_title' => $application->program?->title,
                    'program_price' => $programPrice,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'max_installments' => max(1, (int) ($application->program?->max_installments ?? 1)),
                    'completed_installments' => (int) ($application->successful_payments_count ?? 0),
                ];
            })
            ->values();

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'status', 'program_id']),
            'programs' => \App\Models\Program::query()->select('id', 'title')->orderBy('title')->get(),
            'acceptedApplications' => $acceptedApplications,
            'stats' => [
                'successful_count' => Payment::where('status', 'successful')->count(),
                'pending_count' => Payment::where('status', 'pending')->count(),
                'failed_count' => Payment::where('status', 'failed')->count(),
                'total_collected' => (float) Payment::where('status', 'successful')->sum('amount'),
            ],
        ]);
    }

    public function storeManual(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'application_id' => ['required', 'exists:applications,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'provider' => ['required', 'in:MTN,ORANGE,CASH,BANK_TRANSFER,OTHER'],
            'payment_channel' => ['required', 'in:ONSITE,BANK_TRANSFER,CASH,OTHER'],
            'payer_phone' => ['required', 'string', 'min:8', 'max:30'],
            'status' => ['required', 'in:successful,pending,failed'],
            'payment_type' => ['required', 'in:full,installment'],
            'paid_at' => ['nullable', 'date'],
            'failure_reason' => ['nullable', 'string', 'max:1000'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $payment = DB::transaction(function () use ($validated) {
            $application = Application::query()
                ->whereKey($validated['application_id'])
                ->where('status', 'accepted')
                ->with('program')
                ->lockForUpdate()
                ->firstOrFail();

            $successfulPayments = $application->payments()
                ->where('status', 'successful')
                ->orderBy('paid_at')
                ->get(['id', 'amount']);

            $programPrice = (float) ($application->program?->price ?? 0);
            $paidAmount = (float) $successfulPayments->sum('amount');
            $remainingAmount = max(0, round($programPrice - $paidAmount, 2));
            $maxInstallments = max(1, (int) ($application->program?->max_installments ?? 1));
            $nextInstallment = min($maxInstallments, $successfulPayments->count() + 1);
            $amount = round((float) $validated['amount'], 2);

            if ($validated['status'] === 'successful' && $programPrice > 0 && $amount > $remainingAmount) {
                abort(422, 'Amount exceeds the remaining balance for this application.');
            }

            $effectivePaymentType = $validated['payment_type'];

            if ($validated['status'] === 'successful' && $programPrice > 0) {
                $effectivePaymentType = $amount < $remainingAmount ? 'installment' : 'full';
            }

            $installmentNumber = $effectivePaymentType === 'installment' ? $nextInstallment : $maxInstallments;

            return Payment::create([
                'application_id' => $application->id,
                'user_id' => $application->user_id,
                'updated_by' => (int) auth()->id(),
                'program_id' => $application->program_id,
                'reference' => $this->buildReference(),
                'receipt_number' => null,
                'mesomb_transaction_id' => null,
                'payer_phone' => $validated['payer_phone'],
                'provider' => $validated['provider'],
                'payment_channel' => $validated['payment_channel'],
                'amount' => $amount,
                'currency' => 'XAF',
                'payment_type' => $effectivePaymentType,
                'installment_number' => $installmentNumber,
                'total_installments' => $maxInstallments,
                'status' => $validated['status'],
                'manual_entry' => true,
                'failure_reason' => $validated['failure_reason'] ?? null,
                'admin_notes' => $validated['admin_notes'] ?? null,
                'paid_at' => $validated['status'] === 'successful' ? ($validated['paid_at'] ?? now()) : null,
                'raw_response' => [
                    'source' => 'admin_manual',
                    'recorded_by' => auth()->id(),
                ],
            ]);
        });

        if ($payment->status === 'successful' && empty($payment->receipt_number)) {
            $payment->update([
                'receipt_number' => $this->buildReceiptNumber($payment),
            ]);
        }

        if ($payment->status === 'successful') {
            $payment->load(['application.program', 'user']);
            $payment->user?->notify(new PaymentReceiptNotification($payment));
        }

        return back()->with('success', 'Manual payment recorded successfully.');
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:255', 'unique:payments,reference,'.$payment->id],
            'receipt_number' => ['nullable', 'string', 'max:255', 'unique:payments,receipt_number,'.$payment->id],
            'payer_phone' => ['required', 'string', 'min:8', 'max:30'],
            'provider' => ['required', 'in:MTN,ORANGE,CASH,BANK_TRANSFER,OTHER'],
            'payment_channel' => ['nullable', 'in:ONLINE,ONSITE,BANK_TRANSFER,CASH,OTHER'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_type' => ['required', 'in:full,installment'],
            'installment_number' => ['required', 'integer', 'min:1'],
            'total_installments' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:pending,successful,failed'],
            'paid_at' => ['nullable', 'date'],
            'mesomb_transaction_id' => ['nullable', 'string', 'max:255'],
            'failure_reason' => ['nullable', 'string', 'max:1000'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'manual_entry' => ['sometimes', 'boolean'],
        ]);

        $payload = [
            'reference' => $validated['reference'],
            'receipt_number' => $validated['receipt_number'] ?? null,
            'payer_phone' => $validated['payer_phone'],
            'provider' => $validated['provider'],
            'payment_channel' => $validated['payment_channel'] ?? null,
            'amount' => round((float) $validated['amount'], 2),
            'payment_type' => $validated['payment_type'],
            'installment_number' => (int) $validated['installment_number'],
            'total_installments' => (int) $validated['total_installments'],
            'status' => $validated['status'],
            'mesomb_transaction_id' => $validated['mesomb_transaction_id'] ?? null,
            'failure_reason' => $validated['failure_reason'] ?? null,
            'admin_notes' => $validated['admin_notes'] ?? null,
            'manual_entry' => (bool) ($validated['manual_entry'] ?? $payment->manual_entry),
            'updated_by' => (int) auth()->id(),
        ];

        if ($validated['status'] === 'successful') {
            $payload['paid_at'] = $validated['paid_at'] ?? now();

            if (empty($payload['receipt_number'])) {
                $payload['receipt_number'] = $this->buildReceiptNumber($payment);
            }
        } else {
            $payload['paid_at'] = null;

            if ($validated['status'] !== 'failed') {
                $payload['failure_reason'] = null;
            }
        }

        $payment->update($payload);

        return back()->with('success', 'Payment updated successfully.');
    }

    private function buildReference(): string
    {
        return 'MAN-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
    }

    private function buildReceiptNumber(Payment $payment): string
    {
        return 'RCT-'.now()->format('Ymd').'-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }
}
