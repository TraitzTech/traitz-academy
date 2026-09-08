<?php

namespace App\Http\Controllers\Community\Concerns;

use App\Models\CommunityMember;
use App\Services\Tac\CommunityEnrollmentService;
use Illuminate\Http\Request;

/**
 * Public community actions (RSVP, competition entry) work for signed-in
 * members and for people who have never made an account. This resolves which
 * member is acting, creating the membership on the spot when needed — so
 * RSVPing to a workshop is itself a way into TAC.
 */
trait ResolvesCommunityMember
{
    /**
     * @param  array<string, mixed>  $attributes  extra details from the form
     */
    protected function resolveMember(
        Request $request,
        array $attributes = [],
        string $source = CommunityMember::SOURCE_JOIN_FORM,
    ): ?CommunityMember {
        $enrollment = app(CommunityEnrollmentService::class);

        // A signed-in user is authoritative: never let a form's email field
        // attach somebody else's membership to this session.
        if ($user = $request->user()) {
            $member = $enrollment->linkUser($user);

            if ($member) {
                return $member;
            }

            return $enrollment->record(
                email: $user->email,
                attributes: [
                    ...$attributes,
                    ...CommunityEnrollmentService::splitName($user->name),
                    'user_id' => $user->id,
                ],
                source: $source,
                notify: true,
            );
        }

        $email = $request->input('email');

        if (blank($email)) {
            return null;
        }

        return $enrollment->record(
            email: $email,
            attributes: $attributes,
            source: $source,
            notify: true,
        );
    }

    /**
     * The member behind the current session, without creating one.
     */
    protected function currentMember(Request $request): ?CommunityMember
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        return CommunityMember::query()
            ->where('user_id', $user->id)
            ->orWhere('email', mb_strtolower($user->email))
            ->first();
    }
}
