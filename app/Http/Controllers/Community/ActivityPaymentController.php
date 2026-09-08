<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Community\Concerns\ResolvesCommunityMember;
use App\Http\Controllers\Controller;
use App\Models\TacActivity;
use App\Models\TacActivityRsvp;
use App\Notifications\Tac\TacRsvpConfirmation;
use App\Services\Tac\CommunityEnrollmentService;
use App\Support\Payments\Contracts\PaymentGateway;
use App\Support\Payments\MesombCollectPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Payment for paid workshops, bootcamps and trainings, via the same MeSomb
 * (MTN MoMo / Orange Money) gateway the rest of the academy already uses.
 */
class ActivityPaymentController extends Controller
{
    use ResolvesCommunityMember;

    public function checkout(Request $request, TacActivity $activity): Response|RedirectResponse
    {
        if (! $activity->is_paid || $activity->price <= 0) {
            return redirect()->route('community.activities.show', $activity);
        }

        $member = $this->currentMember($request);
        $rsvp = $this->pendingRsvp($request, $activity, $member);

        if (! $rsvp) {
            return redirect()
                ->route('community.activities.show', $activity)
                ->with('error', 'Reserve your place first, then complete payment.');
        }

        if ($rsvp->payment_status === TacActivityRsvp::PAYMENT_PAID) {
            return redirect()
                ->route('community.activities.show', $activity)
                ->with('info', 'This place is already paid for.');
        }

        return Inertia::render('Community/Activities/Checkout', [
            'activity' => $activity->only(['id', 'title', 'slug', 'type', 'starts_at', 'location', 'price', 'currency', 'cover_image']),
            'rsvp' => $rsvp->only(['id', 'status', 'payment_status', 'amount', 'currency', 'payment_phone']),
            'member' => $rsvp->member?->only(['first_name', 'last_name', 'email', 'phone']),
        ]);
    }

    public function pay(Request $request, TacActivity $activity, PaymentGateway $gateway): RedirectResponse
    {
        abort_unless($activity->is_paid && $activity->price > 0, 404);

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:40'],
            'provider' => ['required', 'in:MTN,ORANGE'],
        ]);

        $member = $this->currentMember($request);
        $rsvp = $this->pendingRsvp($request, $activity, $member);

        if (! $rsvp) {
            return redirect()
                ->route('community.activities.show', $activity)
                ->with('error', 'We could not find your reservation. Please register again.');
        }

        if ($rsvp->payment_status === TacActivityRsvp::PAYMENT_PAID) {
            return redirect()
                ->route('community.activities.show', $activity)
                ->with('info', 'This place is already paid for.');
        }

        $member = $rsvp->member;
        $names = CommunityEnrollmentService::splitName($member->full_name);

        try {
            $result = $gateway->collect(MesombCollectPayload::singleProduct(
                payerPhone: $validated['phone'],
                amount: (int) $activity->price,
                provider: $validated['provider'],
                currency: $activity->currency ?: (string) config('services.mesomb.currency', 'XAF'),
                customerEmail: $member->email,
                customerFirstName: $names['first_name'] ?: $member->first_name,
                customerLastName: $names['last_name'] ?: ($member->last_name ?: $member->first_name),
                productId: 'tac-activity-'.$activity->id,
                productName: $activity->title,
                productCategory: (string) config('services.mesomb.community_product_category', 'professional-training'),
                productLineAmount: (float) $activity->price,
            ));
        } catch (\Throwable $e) {
            Log::error('TAC activity payment failed', [
                'activity_id' => $activity->id,
                'rsvp_id' => $rsvp->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', $this->gatewayErrorMessage($e));
        }

        if (! $result->isSuccessful()) {
            $rsvp->update([
                'payment_status' => TacActivityRsvp::PAYMENT_FAILED,
                'payment_phone' => $validated['phone'],
            ]);

            return back()->with('error', $result->message ?: 'Payment was not completed. Please try again.');
        }

        $rsvp->update([
            'payment_status' => TacActivityRsvp::PAYMENT_PAID,
            'status' => TacActivityRsvp::STATUS_CONFIRMED,
            'payment_reference' => $result->transactionId,
            'payment_phone' => $validated['phone'],
            'paid_at' => now(),
        ]);

        $activity->syncRsvpCount();

        if ($member->isMailable()) {
            $member->notify(new TacRsvpConfirmation($rsvp->fresh(['activity', 'member'])));
        }

        return redirect()
            ->route('community.activities.show', $activity)
            ->with('success', "Payment received. Your place at “{$activity->title}” is confirmed.");
    }

    /**
     * The reservation this session is paying for. A signed-in member is matched
     * by their member record; a guest by the RSVP id held in their session when
     * they registered, so one person can never pay against another's place.
     */
    private function pendingRsvp(Request $request, TacActivity $activity, $member): ?TacActivityRsvp
    {
        if ($member) {
            return $activity->rsvps()
                ->where('community_member_id', $member->id)
                ->whereNot('status', TacActivityRsvp::STATUS_CANCELLED)
                ->with('member')
                ->first();
        }

        $rsvpId = $request->session()->get("tac_rsvp.{$activity->id}");

        return $rsvpId
            ? $activity->rsvps()->whereKey($rsvpId)->with('member')->first()
            : null;
    }

    private function gatewayErrorMessage(\Throwable $e): string
    {
        return match (true) {
            str_contains($e->getMessage(), 'credentials') => 'Payment service is not configured. Please contact us.',
            str_contains($e->getMessage(), 'Service') => 'That payment provider is temporarily unavailable. Please try the other one.',
            default => 'We could not complete the payment. Please try again.',
        };
    }
}
