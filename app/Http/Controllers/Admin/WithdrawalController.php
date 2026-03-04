<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SetWithdrawalPinRequest;
use App\Http\Requests\Admin\WithdrawFundsRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Withdrawal;
use App\Support\Payments\MesombDepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WithdrawalController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $withdrawals = Withdrawal::query()
            ->with('user:id,name,role')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total_withdrawn' => (int) Withdrawal::query()->where('status', Withdrawal::STATUS_SUCCESS)->sum('amount'),
            'pending_count' => Withdrawal::query()->where('status', Withdrawal::STATUS_PENDING)->count(),
            'successful_count' => Withdrawal::query()->where('status', Withdrawal::STATUS_SUCCESS)->count(),
            'failed_count' => Withdrawal::query()->where('status', Withdrawal::STATUS_FAILED)->count(),
        ];

        return Inertia::render('Admin/Withdrawals/Index', [
            'withdrawals' => $withdrawals,
            'stats' => $stats,
            'hasPin' => $user->hasWithdrawalPin(),
            'services' => Withdrawal::availableServices(),
        ]);
    }

    public function setPin(SetWithdrawalPinRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasWithdrawalPin()) {
            if (! Hash::check($request->validated('current_pin'), $user->withdrawal_pin)) {
                return back()->withErrors(['current_pin' => 'Your current PIN is incorrect.']);
            }
        }

        $user->update([
            'withdrawal_pin' => $request->validated('pin'),
        ]);

        return back()->with('success', 'Withdrawal PIN has been set successfully.');
    }

    /**
     * Verify MoMo account holder name via MeSomb Contact Info API.
     */
    public function verifyAccount(Request $request): JsonResponse
    {
        $request->validate([
            'service' => ['required', 'string', 'in:'.implode(',', Withdrawal::availableServices())],
            'receiver' => ['required', 'string', 'regex:/^6\d{8}$/'],
        ]);

        try {
            $depositService = app(MesombDepositService::class);

            $contactInfo = $depositService->getContactInfo([
                'provider' => $request->input('service'),
                'service_key' => $request->input('receiver'),
                'country' => (string) config('services.mesomb.country', 'CM'),
            ]);

            $fullName = trim(($contactInfo['first_name'] ?? '').' '.($contactInfo['last_name'] ?? ''));

            if ($fullName === '') {
                return response()->json([
                    'success' => false,
                    'can_proceed_manually' => true,
                    'message' => 'Could not retrieve the account holder name from MeSomb. You can enter it manually to proceed.',
                ]);
            }

            return response()->json([
                'success' => true,
                'account_name' => $fullName,
                'first_name' => $contactInfo['first_name'] ?? '',
                'last_name' => $contactInfo['last_name'] ?? '',
            ]);
        } catch (\Exception $e) {
            Log::warning('Account verification failed', [
                'receiver' => $request->input('receiver'),
                'service' => $request->input('service'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'can_proceed_manually' => true,
                'message' => 'MeSomb could not verify this number automatically. You can enter the account holder name manually to proceed.',
            ]);
        }
    }

    public function withdraw(WithdrawFundsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if (! Hash::check($validated['pin'], $user->withdrawal_pin)) {
            return back()->withErrors(['pin' => 'Invalid PIN. Please try again.']);
        }

        $withdrawal = Withdrawal::query()->create([
            'user_id' => $user->id,
            'amount' => $validated['amount'],
            'service' => $validated['service'],
            'receiver' => $validated['receiver'],
            'receiver_name' => $validated['receiver_name'],
            'currency' => (string) config('services.mesomb.currency', 'XAF'),
            'country' => (string) config('services.mesomb.country', 'CM'),
            'status' => Withdrawal::STATUS_PENDING,
        ]);

        try {
            $depositService = app(MesombDepositService::class);

            $response = $depositService->deposit([
                'amount' => $validated['amount'],
                'service' => $validated['service'],
                'receiver' => $validated['receiver'],
                'country' => (string) config('services.mesomb.country', 'CM'),
                'currency' => (string) config('services.mesomb.currency', 'XAF'),
                'trxID' => 'WD-'.$withdrawal->id.'-'.Str::random(8),
            ]);

            $transactionId = null;
            $reference = null;

            if (property_exists($response, 'transaction') && $response->transaction) {
                $transactionId = $response->transaction->id ?? $response->transaction->pk ?? null;
            }
            if (property_exists($response, 'reference')) {
                $reference = $response->reference;
            }

            $encoded = json_encode($response);
            $rawResponse = is_string($encoded) ? json_decode($encoded, true) : null;

            $isSuccess = $response->isOperationSuccess() && $response->isTransactionSuccess();

            $withdrawal->update([
                'status' => $isSuccess ? Withdrawal::STATUS_SUCCESS : Withdrawal::STATUS_FAILED,
                'mesomb_transaction_id' => $transactionId,
                'reference' => $reference,
                'failure_reason' => $isSuccess ? null : ($response->message ?? 'Transaction failed at provider.'),
                'raw_response' => $rawResponse,
            ]);

            if ($isSuccess) {
                $this->recordExpense($withdrawal, $user);

                return back()->with('success', 'Withdrawal of '.number_format($validated['amount']).' XAF to '.$validated['receiver_name'].' was successful.');
            }

            return back()->withErrors(['withdrawal' => $response->message ?? 'Withdrawal failed. Please try again.']);
        } catch (\Exception $e) {
            Log::error('Withdrawal failed', [
                'withdrawal_id' => $withdrawal->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $withdrawal->update([
                'status' => Withdrawal::STATUS_FAILED,
                'failure_reason' => $e->getMessage(),
            ]);

            return back()->withErrors(['withdrawal' => 'Withdrawal failed: '.$e->getMessage()]);
        }
    }

    /**
     * Auto-record a successful withdrawal as an expense.
     */
    private function recordExpense(Withdrawal $withdrawal, \App\Models\User $user): void
    {
        try {
            $category = ExpenseCategory::query()->firstOrCreate(
                ['slug' => 'momo-withdrawals'],
                [
                    'name' => 'MoMo Withdrawals',
                    'description' => 'Funds withdrawn from MeSomb to mobile money accounts.',
                    'color' => '#42b6c5',
                    'is_active' => true,
                ],
            );

            Expense::query()->create([
                'expense_category_id' => $category->id,
                'recorded_by' => $user->id,
                'title' => 'MoMo Withdrawal to '.$withdrawal->receiver_name,
                'description' => 'Withdrawal #'.$withdrawal->id.' — '.$withdrawal->service.' '.$withdrawal->receiver,
                'amount' => $withdrawal->amount,
                'currency' => $withdrawal->currency,
                'payment_method' => 'mobile_money',
                'receipt_reference' => $withdrawal->reference ?? 'WD-'.$withdrawal->id,
                'vendor' => $withdrawal->service.' Mobile Money',
                'expense_date' => now()->toDateString(),
                'status' => 'approved',
                'notes' => 'Auto-recorded from withdrawal. MeSomb TXN: '.($withdrawal->mesomb_transaction_id ?? 'N/A'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record withdrawal expense', [
                'withdrawal_id' => $withdrawal->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
