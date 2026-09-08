<?php

namespace App\Services\Tac;

use App\Models\CommunityMember;
use App\Models\TacTrack;
use App\Models\User;
use App\Notifications\Tac\TacJoinConfirmation;
use App\Notifications\Tac\TacWelcomeNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The single entry point for putting somebody into the Traitz Academy Community.
 *
 * Every path into TAC — the public Join form, a program application, an event
 * registration, AI Forge, a course enrollment, an internship, an admin add, a
 * backfill — funnels through {@see record()}. It is idempotent on email, so a
 * person who registers for five things stays one member.
 */
class CommunityEnrollmentService
{
    /**
     * Create or update the community member behind an email address.
     *
     * Existing members are enriched, never overwritten: a field already filled
     * in (by the member themselves or an earlier, richer registration) wins
     * over whatever this registration happens to carry. That keeps a bare
     * event signup from blanking out a curated profile.
     *
     * @param  array<string, mixed>  $attributes  first_name, last_name, phone, school,
     *                                            current_status, heard_about, bio, user_id
     * @param  array<int, int>  $trackIds  tracks of interest to attach (additive)
     * @param  bool  $notify  send the welcome/confirmation email for brand-new members
     */
    public function record(
        string $email,
        array $attributes = [],
        string $source = CommunityMember::SOURCE_JOIN_FORM,
        ?Model $sourceable = null,
        array $trackIds = [],
        bool $notify = true,
        ?string $context = null,
    ): ?CommunityMember {
        $email = Str::lower(trim($email));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        /** @var array{0: CommunityMember, 1: bool} $result */
        $result = DB::transaction(function () use ($email, $attributes, $source, $sourceable, $trackIds) {
            $member = CommunityMember::query()->where('email', $email)->first();
            $isNew = $member === null;

            if ($isNew) {
                $member = new CommunityMember([
                    'email' => $email,
                    'source' => $source,
                    'joined_at' => now(),
                ]);

                if ($sourceable) {
                    $member->sourceable_type = $sourceable->getMorphClass();
                    $member->sourceable_id = $sourceable->getKey();
                }
            }

            $this->enrich($member, $attributes);

            // A person who came in through an event and later gets a real
            // account should have the two linked, whenever we learn about it.
            if (blank($member->user_id)) {
                $member->user_id = $attributes['user_id'] ?? $this->findUserIdByEmail($email);
            }

            if (blank($member->first_name)) {
                $member->first_name = Str::before($email, '@');
            }

            $member->save();

            if ($trackIds !== []) {
                $this->attachTracks($member, $trackIds);
            }

            return [$member, $isNew];
        });

        [$member, $isNew] = $result;

        if ($notify && $isNew) {
            $this->sendWelcome($member, $source, $context);
        }

        return $member;
    }

    /**
     * Fill in only the blanks — an existing value is never clobbered.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function enrich(CommunityMember $member, array $attributes): void
    {
        $enrichable = [
            'first_name',
            'last_name',
            'phone',
            'school',
            'heard_about',
            'bio',
            'avatar_path',
        ];

        foreach ($enrichable as $field) {
            $incoming = $attributes[$field] ?? null;

            if (filled($incoming) && blank($member->{$field})) {
                $member->{$field} = $incoming;
            }
        }

        // current_status defaults to "student"; a registration may refine it
        // only while the member is still sitting on that default, so a member
        // who told us they are a past intern is not demoted by a later signup.
        $status = $attributes['current_status'] ?? null;
        $stillDefault = blank($member->current_status) || $member->current_status === CommunityMember::STATUS_STUDENT;

        if (filled($status) && $stillDefault) {
            $member->current_status = $status;
        }
    }

    /**
     * Attach tracks of interest without detaching ones already chosen. The
     * first track a member ever picks becomes their primary.
     *
     * @param  array<int, int>  $trackIds
     */
    public function attachTracks(CommunityMember $member, array $trackIds): void
    {
        $valid = TacTrack::query()
            ->whereIn('id', array_filter($trackIds))
            ->pluck('id')
            ->all();

        if ($valid === []) {
            return;
        }

        $existing = $member->tracks()->pluck('tac_tracks.id')->all();
        $hasPrimary = $member->tracks()->wherePivot('is_primary', true)->exists();

        $payload = [];
        foreach ($valid as $index => $trackId) {
            if (in_array($trackId, $existing, true)) {
                continue;
            }

            $payload[$trackId] = ['is_primary' => ! $hasPrimary && $index === 0];
            $hasPrimary = $hasPrimary || $index === 0;
        }

        if ($payload !== []) {
            $member->tracks()->attach($payload);
        }
    }

    /**
     * Link an account to its community member record, in both directions.
     * Called when a user signs up or changes their email.
     */
    public function linkUser(User $user): ?CommunityMember
    {
        $email = Str::lower(trim((string) $user->email));

        if ($email === '') {
            return null;
        }

        $member = CommunityMember::query()->where('email', $email)->first();

        if (! $member) {
            return null;
        }

        if ($member->user_id !== $user->id) {
            $member->forceFill(['user_id' => $user->id])->save();
        }

        return $member;
    }

    /**
     * Send the right first-contact email. Someone who deliberately joined gets
     * a confirmation; someone auto-included from a registration gets a welcome
     * that explains why they are hearing from TAC.
     */
    protected function sendWelcome(CommunityMember $member, string $source, ?string $context): void
    {
        if (! $member->isMailable()) {
            return;
        }

        try {
            $notification = $source === CommunityMember::SOURCE_JOIN_FORM
                ? new TacJoinConfirmation($member)
                : new TacWelcomeNotification($member, $context);

            $member->notify($notification);

            $member->forceFill(['welcomed_at' => now()])->save();
        } catch (\Throwable $e) {
            // Never let a mail failure break the registration that triggered it.
            Log::warning('TAC welcome email failed', [
                'community_member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function findUserIdByEmail(string $email): ?int
    {
        return User::query()->where('email', $email)->value('id');
    }

    /**
     * Split a single display name into first/last for sources that only store
     * one name field (course enrollments, internships).
     *
     * @return array{first_name: string, last_name: string|null}
     */
    public static function splitName(?string $name): array
    {
        $name = trim((string) $name);

        if ($name === '') {
            return ['first_name' => '', 'last_name' => null];
        }

        $parts = preg_split('/\s+/', $name) ?: [$name];
        $first = array_shift($parts);

        return [
            'first_name' => $first,
            'last_name' => $parts === [] ? null : implode(' ', $parts),
        ];
    }
}
