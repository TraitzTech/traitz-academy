<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentReceiptNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $authUser = $request->user();

        $query = $this->newPaymentsBaseQuery();

        $this->applyPaymentVisibilityScope($query, $authUser);

        $this->applyPaymentFilters($query, $request);

        $this->applyCollectorFilters($query, $request, $authUser);

        $payments = $query->latest()->paginate(20)->withQueryString();

        $acceptedApplications = Application::query()
            ->where('status', 'accepted')
            ->with(['program:id,title,price,max_installments', 'user:id,name,email'])
            ->withSum(['payments as successful_payments_sum_amount' => function ($paymentQuery) {
                $paymentQuery->where('status', 'successful');
            }], 'base_amount')
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
                    'program_id' => $application->program_id,
                    'program_title' => $application->program?->title,
                    'program_price' => $programPrice,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'max_installments' => max(1, (int) ($application->program?->max_installments ?? 1)),
                    'completed_installments' => (int) ($application->successful_payments_count ?? 0),
                ];
            })
            ->values();

        $totalOutstanding = (float) $acceptedApplications->sum('remaining_amount');

        $successfulCountQuery = Payment::query()->where('status', 'successful');
        $pendingCountQuery = Payment::query()->where('status', 'pending');
        $failedCountQuery = Payment::query()->where('status', 'failed');
        $totalCollectedQuery = Payment::query()->where('status', 'successful');

        $this->applyPaymentVisibilityScope($successfulCountQuery, $authUser);
        $this->applyPaymentVisibilityScope($pendingCountQuery, $authUser);
        $this->applyPaymentVisibilityScope($failedCountQuery, $authUser);
        $this->applyPaymentVisibilityScope($totalCollectedQuery, $authUser);

        $this->applyCollectorFilters($successfulCountQuery, $request, $authUser);
        $this->applyCollectorFilters($pendingCountQuery, $request, $authUser);
        $this->applyCollectorFilters($failedCountQuery, $request, $authUser);
        $this->applyCollectorFilters($totalCollectedQuery, $request, $authUser);

        $this->applyPaymentFilters($successfulCountQuery, $request, includeSearch: false, includeStatus: false);
        $this->applyPaymentFilters($pendingCountQuery, $request, includeSearch: false, includeStatus: false);
        $this->applyPaymentFilters($failedCountQuery, $request, includeSearch: false, includeStatus: false);
        $this->applyPaymentFilters($totalCollectedQuery, $request, includeSearch: false, includeStatus: false);

        $collectors = collect();
        $collectorRoles = [];

        if ($authUser->isExecutive()) {
            $adminRoles = [
                User::ROLE_CTO,
                User::ROLE_CEO,
                User::ROLE_PROGRAM_COORDINATOR,
                User::ROLE_ADMIN_LEGACY,
            ];

            $collectors = User::query()
                ->select('id', 'name', 'role')
                ->whereIn('role', $adminRoles)
                ->orderBy('name')
                ->get();

            $collectorRoles = [
                User::ROLE_CTO,
                User::ROLE_CEO,
                User::ROLE_PROGRAM_COORDINATOR,
            ];
        }

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'status', 'program_id', 'payment_source', 'collected_by', 'collector_role']),
            'programs' => \App\Models\Program::query()->select('id', 'title')->orderBy('title')->get(),
            'collectors' => $collectors,
            'collectorRoles' => $collectorRoles,
            'acceptedApplications' => $acceptedApplications,
            'stats' => [
                'successful_count' => $successfulCountQuery->count(),
                'pending_count' => $pendingCountQuery->count(),
                'failed_count' => $failedCountQuery->count(),
                'total_collected' => (float) $totalCollectedQuery->sum('amount'),
                'total_outstanding' => $totalOutstanding,
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->isExecutive(), 403);

        $query = $this->newPaymentsBaseQuery();

        $this->applyPaymentVisibilityScope($query, $request->user());
        $this->applyPaymentFilters($query, $request);
        $this->applyCollectorFilters($query, $request, $request->user());

        $filename = 'payments-export-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fputcsv($output, [
                'Receipt Number',
                'Reference',
                'Applicant Name',
                'Applicant Email',
                'Program',
                'Amount',
                'Currency',
                'Status',
                'Payment Type',
                'Source',
                'Payment Channel',
                'Provider',
                'Collected By',
                'Collected By Role',
                'Paid At',
                'Created At',
            ]);

            $query->latest('payments.id')
                ->chunk(500, function ($payments) use ($output): void {
                    foreach ($payments as $payment) {
                        $collector = $payment->recordedBy ?? $payment->updatedBy;

                        fputcsv($output, [
                            $payment->receipt_number,
                            $payment->reference,
                            trim(($payment->application?->first_name ?? '').' '.($payment->application?->last_name ?? '')),
                            $payment->application?->email,
                            $payment->program?->title,
                            $payment->amount,
                            $payment->currency,
                            $payment->status,
                            $payment->payment_type,
                            $payment->manual_entry ? 'manual' : 'online',
                            $payment->payment_channel,
                            $payment->provider,
                            $collector?->name,
                            $collector?->role,
                            optional($payment->paid_at)?->toDateTimeString(),
                            optional($payment->created_at)?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
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
                ->get(['id', 'base_amount']);

            $programPrice = (float) ($application->program?->price ?? 0);
            $paidAmount = (float) $successfulPayments->sum('base_amount');
            $remainingAmount = max(0, round($programPrice - $paidAmount, 2));
            $maxInstallments = max(1, (int) ($application->program?->max_installments ?? 1));
            $nextInstallment = min($maxInstallments, $successfulPayments->count() + 1);
            $amount = round((float) $validated['amount'], 2);

            if ($validated['status'] === 'successful' && $programPrice > 0 && $amount > ($remainingAmount + 0.01)) {
                throw ValidationException::withMessages([
                    'amount' => 'Amount exceeds remaining balance ('.number_format($remainingAmount, 2).' XAF).',
                ]);
            }

            $effectivePaymentType = $validated['payment_type'];

            if ($validated['status'] === 'successful' && $programPrice > 0) {
                $effectivePaymentType = $amount < $remainingAmount ? 'installment' : 'full';
            }

            $installmentNumber = $effectivePaymentType === 'installment' ? $nextInstallment : $maxInstallments;

            return Payment::create([
                'application_id' => $application->id,
                'user_id' => $application->user_id,
                'recorded_by' => (int) auth()->id(),
                'updated_by' => (int) auth()->id(),
                'program_id' => $application->program_id,
                'reference' => $this->buildReference(),
                'receipt_number' => null,
                'mesomb_transaction_id' => null,
                'payer_phone' => $validated['payer_phone'],
                'provider' => $validated['provider'],
                'payment_channel' => $validated['payment_channel'],
                'amount' => $amount,
                'base_amount' => $amount,
                'surcharge_amount' => 0,
                'surcharge_percentage' => 0,
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
        abort_unless($request->user()->canManagePaymentRecord($payment), 403);

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

    public function verify(Request $request): Response
    {
        $search = trim($request->string('receipt')->toString());

        $payment = null;

        if ($search !== '') {
            $query = Payment::query()
                ->with([
                    'program:id,title',
                    'application:id,first_name,last_name,email',
                    'user:id,name,email',
                    'recordedBy:id,name,role',
                    'updatedBy:id,name,role',
                ])
                ->where(function ($query) use ($search) {
                    $query->where('receipt_number', $search)
                        ->orWhere('reference', $search)
                        ->orWhere('mesomb_transaction_id', $search);
                });

            $this->applyPaymentVisibilityScope($query, $request->user());

            $payment = $query->first();
        }

        return Inertia::render('Admin/Payments/Verify', [
            'search' => $search,
            'payment' => $payment,
        ]);
    }

    private function buildReference(): string
    {
        return 'MAN-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
    }

    private function applyPaymentVisibilityScope(Builder $query, User $user): void
    {
        if (! $user->isProgramCoordinator()) {
            return;
        }

        $query->where('manual_entry', true)
            ->where(function (Builder $builder) use ($user) {
                $builder->where('recorded_by', $user->id)
                    ->orWhere(function (Builder $fallbackQuery) use ($user) {
                        $fallbackQuery->whereNull('recorded_by')
                            ->where('updated_by', $user->id);
                    });
            });
    }

    private function applyCollectorFilters(Builder $query, Request $request, User $user): void
    {
        if (! $user->isExecutive()) {
            return;
        }

        if ($request->filled('collected_by')) {
            $collectorId = $request->integer('collected_by');

            if ($collectorId > 0) {
                $query->where('manual_entry', true)
                    ->where(function (Builder $builder) use ($collectorId) {
                        $builder->where('recorded_by', $collectorId)
                            ->orWhere(function (Builder $fallbackQuery) use ($collectorId) {
                                $fallbackQuery->whereNull('recorded_by')
                                    ->where('updated_by', $collectorId);
                            });
                    });
            }
        }

        if ($request->filled('collector_role')) {
            $collectorRole = $request->string('collector_role')->toString();

            $query->where('manual_entry', true)
                ->where(function (Builder $builder) use ($collectorRole) {
                    $builder->whereHas('recordedBy', function (Builder $recordedByQuery) use ($collectorRole) {
                        $recordedByQuery->where('role', $collectorRole);
                    })->orWhere(function (Builder $fallbackQuery) use ($collectorRole) {
                        $fallbackQuery->whereNull('recorded_by')
                            ->whereHas('updatedBy', function (Builder $updatedByQuery) use ($collectorRole) {
                                $updatedByQuery->where('role', $collectorRole);
                            });
                    });
                });
        }
    }

    private function newPaymentsBaseQuery(): Builder
    {
        return Payment::query()->with([
            'program:id,title',
            'application:id,first_name,last_name,email,program_id',
            'user:id,name,email',
            'recordedBy:id,name,role',
            'updatedBy:id,name,role',
        ]);
    }

    private function applyPaymentFilters(Builder $query, Request $request, bool $includeSearch = true, bool $includeStatus = true): void
    {
        if ($includeSearch && $request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('reference', 'like', "%{$search}%")
                    ->orWhere('receipt_number', 'like', "%{$search}%")
                    ->orWhere('payer_phone', 'like', "%{$search}%")
                    ->orWhereHas('application', function (Builder $applicationQuery) use ($search) {
                        $applicationQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($includeStatus && $request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->integer('program_id'));
        }

        if ($request->filled('payment_source')) {
            $source = $request->string('payment_source')->toString();

            if ($source === 'manual') {
                $query->where('manual_entry', true);
            }

            if ($source === 'online') {
                $query->where('manual_entry', false);
            }
        }
    }

    private function buildReceiptNumber(Payment $payment): string
    {
        return 'RCT-'.now()->format('Ymd').'-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }
}
