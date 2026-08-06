<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePayment;
use App\Models\Enrollment;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\Lms\CourseEnrollmentConfirmedNotification;
use App\Notifications\Lms\CoursePaymentFailedNotification;
use App\Notifications\Lms\CoursePaymentReceivedNotification;
use App\Notifications\Lms\EnrollmentAccessSuspendedNotification;
use App\Support\Payments\Contracts\PaymentGateway;
use App\Support\Payments\MesombCollectPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CoursePaymentController extends Controller
{
    public function __construct(private PaymentGateway $paymentGateway) {}

    public function checkout(Course $course): Response|RedirectResponse
    {
        abort_unless($course->status === 'published', 404);

        $userId = (int) auth()->id();

        $summary = $this->buildCoursePaymentSummary($course, $userId);

        if ($summary['course_price'] <= 0) {
            return redirect()
                ->route('lms.courses.show', $course)
                ->with('info', 'This course is free. Enroll from the course page.');
        }

        if ($summary['remaining_amount'] <= 0) {
            return redirect()
                ->route('lms.my-courses')
                ->with('info', 'This course is already paid for.');
        }

        $user = auth()->user();

        return Inertia::render('Lms/CourseCheckout', [
            'course' => $course->only([
                'id', 'title', 'slug', 'price', 'sale_price', 'cover_image', 'max_installments',
            ]),
            'summary' => $summary,
            'defaultPhone' => $user->phone ?? '',
        ]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->status === 'published', 404);

        $validated = $request->validate([
            'payer_phone' => ['required', 'string', 'min:8', 'max:20'],
            'provider' => ['required', 'in:MTN,ORANGE'],
            'payment_mode' => ['required', 'in:full,installment'],
        ]);

        $userId = (int) auth()->id();

        $sanitizedPayerPhone = preg_replace('/\D/', '', $validated['payer_phone']);
        if (str_starts_with($sanitizedPayerPhone, '237') && strlen($sanitizedPayerPhone) > 9) {
            $sanitizedPayerPhone = substr($sanitizedPayerPhone, 3);
        }

        $payment = DB::transaction(function () use ($course, $userId, $sanitizedPayerPhone, $validated) {
            $lockedCourse = Course::query()
                ->whereKey($course->id)
                ->lockForUpdate()
                ->firstOrFail();

            $summary = $this->buildCoursePaymentSummary($lockedCourse, $userId);

            if ($summary['course_price'] <= 0) {
                abort(422, 'This course does not require payment.');
            }

            if ($summary['remaining_amount'] <= 0) {
                abort(422, 'This course is already paid for.');
            }

            if ($summary['max_installments'] <= 1 && $validated['payment_mode'] === 'installment') {
                abort(422, 'Installment payments are not enabled for this course.');
            }

            $isInstallmentPayment = $validated['payment_mode'] === 'installment';

            $baseAmount = $isInstallmentPayment
                ? min((float) $summary['installment_amount'], (float) $summary['remaining_amount'])
                : (float) $summary['remaining_amount'];

            $surchargePercentage = (float) $summary['online_surcharge_percentage'];
            $surchargeAmount = round(($baseAmount * $surchargePercentage) / 100, 2);
            $amount = round($baseAmount + $surchargeAmount, 2);

            return CoursePayment::create([
                'user_id' => $userId,
                'course_id' => $lockedCourse->id,
                'reference' => $this->buildReference(),
                'payer_phone' => $sanitizedPayerPhone,
                'provider' => $validated['provider'],
                'amount' => $amount,
                'base_amount' => $baseAmount,
                'surcharge_amount' => $surchargeAmount,
                'surcharge_percentage' => $surchargePercentage,
                'currency' => (string) config('services.mesomb.currency', 'XAF'),
                'payment_type' => $validated['payment_mode'],
                'installment_number' => (int) $summary['next_installment_number'],
                'total_installments' => (int) $summary['max_installments'],
                'status' => 'pending',
                'receipt_number' => null,
            ]);
        });

        $user = auth()->user();
        [$firstName, $lastName] = $this->splitName($user->name);

        $collectAmount = (int) round((float) $payment->amount);
        if ($collectAmount < 1) {
            Log::warning('Course payment amount rounded to zero before collect', [
                'course_payment_id' => $payment->id,
                'amount' => $payment->amount,
            ]);

            return back()->with('error', 'The payment amount is invalid. Please refresh and try again.');
        }

        $course->loadMissing('category');

        try {
            $gatewayResponse = $this->paymentGateway->collect(
                MesombCollectPayload::singleProduct(
                    $sanitizedPayerPhone,
                    $collectAmount,
                    $payment->provider,
                    (string) $payment->currency,
                    (string) $user->email,
                    $firstName,
                    $lastName !== '' ? $lastName : 'Student',
                    (string) $course->id,
                    (string) $course->title,
                    MesombCollectPayload::courseProductCategory($course),
                    (float) $payment->amount,
                )
            );

            if ($gatewayResponse->isSuccessful()) {
                $payment->update([
                    'status' => 'successful',
                    'mesomb_transaction_id' => $gatewayResponse->transactionId,
                    'receipt_number' => $this->buildReceiptNumber($payment),
                    'paid_at' => now(),
                    'failure_reason' => null,
                    'raw_response' => $gatewayResponse->rawResponse,
                ]);

                $lockedCourse = Course::query()->whereKey($course->id)->lockForUpdate()->firstOrFail();
                $paidTotal = (float) CoursePayment::query()
                    ->where('user_id', $userId)
                    ->where('course_id', $lockedCourse->id)
                    ->where('status', 'successful')
                    ->sum('base_amount');
                $coursePrice = $lockedCourse->effectivePrice();
                $fullyPaid = $paidTotal >= $coursePrice - 0.01;

                $enrolmentEvent = DB::transaction(function () use ($lockedCourse, $userId, $fullyPaid, $validated) {
                    $locked = Course::query()->whereKey($lockedCourse->id)->lockForUpdate()->firstOrFail();

                    $enrollment = Enrollment::query()->firstOrCreate(
                        [
                            'user_id' => $userId,
                            'course_id' => $locked->id,
                        ],
                        [
                            'instalment_plan_id' => null,
                            'payment_type' => $fullyPaid ? 'full' : 'instalment',
                            'access_status' => 'active',
                            'progress' => 0,
                        ]
                    );

                    $shouldConfirmEnrolment = $enrollment->wasRecentlyCreated;

                    if ($enrollment->wasRecentlyCreated) {
                        $locked->increment('enrolled_count');
                    } else {
                        $previousStatus = $enrollment->access_status;
                        $enrollment->update([
                            'payment_type' => $fullyPaid ? 'full' : 'instalment',
                            'access_status' => in_array($enrollment->access_status, ['suspended', 'revoked'], true)
                                ? 'active'
                                : $enrollment->access_status,
                        ]);
                        if (in_array($previousStatus, ['suspended', 'revoked'], true)) {
                            $shouldConfirmEnrolment = true;
                        }
                    }

                    $intervalDays = (int) config('lms.instalment_interval_days', 30);
                    if ($fullyPaid) {
                        $enrollment->update([
                            'instalment_next_due_at' => null,
                            'consecutive_failed_payments' => 0,
                        ]);
                    } elseif ($validated['payment_mode'] === 'installment') {
                        $enrollment->update([
                            'instalment_next_due_at' => now()->addDays($intervalDays),
                            'consecutive_failed_payments' => 0,
                            'last_instalment_reminder_sent_at' => null,
                        ]);
                    } else {
                        $enrollment->update([
                            'consecutive_failed_payments' => 0,
                        ]);
                    }

                    return $shouldConfirmEnrolment;
                });

                $user->notify(new CoursePaymentReceivedNotification($payment->fresh()));

                if ($enrolmentEvent) {
                    $user->notify(new CourseEnrollmentConfirmedNotification($course->fresh()));
                }

                $message = $fullyPaid
                    ? 'Payment successful. You are now enrolled.'
                    : 'Payment recorded. You have course access; pay the remaining balance from checkout when ready.';

                return redirect()
                    ->route('lms.course-payments.receipt', $payment)
                    ->with('success', $message);
            }

            $payment->update([
                'status' => 'failed',
                'mesomb_transaction_id' => $gatewayResponse->transactionId,
                'failure_reason' => $gatewayResponse->message,
                'raw_response' => $gatewayResponse->rawResponse,
            ]);

            $this->notifyPaymentFailedAndMaybeSuspend($course, $user, $payment, $gatewayResponse->message);

            return back()->with('error', $gatewayResponse->message ?? 'Payment failed. Please try again.');
        } catch (\Throwable $exception) {
            Log::warning('Course payment gateway collect failed', [
                'course_payment_id' => $payment->id,
                'course_id' => $course->id,
                'message' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);

            $payment->update([
                'status' => 'failed',
                'failure_reason' => $exception->getMessage(),
            ]);

            $this->notifyPaymentFailedAndMaybeSuspend($course, $user, $payment, $exception->getMessage());

            return back()->with('error', 'Payment could not be completed right now. Please try again.');
        }
    }

    public function receipt(CoursePayment $coursePayment): Response
    {
        $this->ensureCanViewCoursePaymentReceipt($coursePayment);

        $coursePayment->load(['course.instructor:id,name', 'course.category:id,name', 'user']);
        $coursePayment->makeHidden(['raw_response']);

        return Inertia::render('Lms/CoursePaymentReceipt', [
            'payment' => $coursePayment,
        ]);
    }

    /**
     * @return array{
     *     course_price: float,
     *     paid_amount: float,
     *     remaining_amount: float,
     *     max_installments: int,
     *     installment_amount: float,
     *     completed_installments: int,
     *     next_installment_number: int,
     *     online_surcharge_percentage: float,
     *     can_pay: bool,
     * }
     */
    private function buildCoursePaymentSummary(Course $course, int $userId): array
    {
        $coursePrice = $course->effectivePrice();
        $maxInstallments = max(1, (int) ($course->max_installments ?? 1));

        $paidAmount = (float) CoursePayment::query()
            ->where('user_id', $userId)
            ->where('course_id', $course->id)
            ->where('status', 'successful')
            ->sum('base_amount');

        $successfulCount = CoursePayment::query()
            ->where('user_id', $userId)
            ->where('course_id', $course->id)
            ->where('status', 'successful')
            ->count();

        $remainingAmount = max(0, round($coursePrice - $paidAmount, 2));

        $installmentAmount = $maxInstallments > 0
            ? round($coursePrice / $maxInstallments, 2)
            : $coursePrice;

        $onlineSurchargePercentage = $this->getOnlineSurchargePercentage();

        return [
            'course_price' => $coursePrice,
            'paid_amount' => round($paidAmount, 2),
            'remaining_amount' => $remainingAmount,
            'max_installments' => $maxInstallments,
            'installment_amount' => $installmentAmount,
            'completed_installments' => $successfulCount,
            'next_installment_number' => min($maxInstallments, $successfulCount + 1),
            'online_surcharge_percentage' => $onlineSurchargePercentage,
            'can_pay' => $coursePrice > 0 && $remainingAmount > 0,
        ];
    }

    private function ensureCanViewCoursePaymentReceipt(CoursePayment $coursePayment): void
    {
        $isAuthorized = auth()->id() === $coursePayment->user_id || auth()->user()?->canAccessAdminPanel();
        abort_unless($isAuthorized, 403);
    }

    private function getOnlineSurchargePercentage(): float
    {
        $configuredPercentage = (float) SiteSetting::get('online_payment_surcharge_percentage', 2);

        return max(0, min(100, round($configuredPercentage, 2)));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(?string $name): array
    {
        $name = trim((string) $name);
        if ($name === '') {
            return ['Guest', ''];
        }

        $parts = preg_split('/\s+/', $name, 2);
        if ($parts === false) {
            return ['Guest', ''];
        }

        return [
            $parts[0] ?? 'Guest',
            $parts[1] ?? '',
        ];
    }

    private function buildReference(): string
    {
        return 'CRS-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
    }

    private function buildReceiptNumber(CoursePayment $payment): string
    {
        return 'RCT-'.now()->format('Ymd').'-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }

    private function notifyPaymentFailedAndMaybeSuspend(Course $course, User $user, CoursePayment $payment, ?string $reason): void
    {
        $user->notify(new CoursePaymentFailedNotification($course, $reason));

        if ($payment->payment_type !== 'installment') {
            return;
        }

        $enrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($enrollment === null) {
            return;
        }

        $summary = $this->buildCoursePaymentSummary($course, (int) $user->id);
        if ($summary['remaining_amount'] <= 0.01) {
            return;
        }

        $enrollment->increment('consecutive_failed_payments');
        $enrollment->refresh();

        $threshold = (int) config('lms.failed_instalment_payments_before_suspend', 3);
        if ($enrollment->consecutive_failed_payments < $threshold) {
            return;
        }

        if ($enrollment->access_status === 'suspended') {
            return;
        }

        $enrollment->update(['access_status' => 'suspended']);
        $user->notify(new EnrollmentAccessSuspendedNotification($course));
    }
}
