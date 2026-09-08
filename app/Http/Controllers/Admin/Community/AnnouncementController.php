<?php

namespace App\Http\Controllers\Admin\Community;

use App\Http\Controllers\Controller;
use App\Models\CommunityMember;
use App\Models\TacTrack;
use App\Models\User;
use App\Notifications\Tac\TacAnnouncementNotification;
use App\Support\EmailContentSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lets a TAC leader email the people they actually lead: a track mentor
 * reaches their track, a school lead their campus, an executive anyone.
 * Mirrors the LMS's tutor/admin "broadcast notification" feature
 * ({@see \App\Http\Controllers\Lms\BroadcastNotificationController}), scoped
 * to TAC's own membership instead of course rosters.
 */
class AnnouncementController extends Controller
{
    public function create(Request $request): Response
    {
        $user = $request->user();
        $isExecutive = $user->hasTacExecutiveAuthority();

        // Aliased to `member_count` (singular) to match the shape the
        // frontend's TrackOption/SchoolOption expect — Laravel's default
        // withCount('members') would otherwise produce `members_count`.
        $myTracks = TacTrack::query()
            ->whereIn('id', $user->tacManagedTrackIds())
            ->withCount(['members as member_count'])
            ->get(['id', 'name']);

        $mySchools = collect($user->tacManagedSchools())
            ->map(fn (string $school) => [
                'name' => $school,
                'member_count' => CommunityMember::query()->mailable()->where('school', $school)->count(),
            ])
            ->values();

        return Inertia::render('Admin/Community/Announcements/Index', [
            'myTracks' => $myTracks,
            'mySchools' => $mySchools,
            'isExecutive' => $isExecutive,
            'allTracks' => $isExecutive
                ? TacTrack::query()->active()->ordered()->withCount(['members as member_count'])->get(['id', 'name'])
                : [],
            'allSchools' => $isExecutive
                ? CommunityMember::query()
                    ->mailable()
                    ->whereNotNull('school')
                    ->where('school', '!=', '')
                    ->selectRaw('school as name, count(*) as member_count')
                    ->groupBy('school')
                    ->orderBy('school')
                    ->get()
                : [],
            'totalMembers' => $isExecutive ? CommunityMember::query()->mailable()->count() : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isExecutive = $user->hasTacExecutiveAuthority();

        $validated = $request->validate([
            'audience' => ['required', Rule::in(['my_track', 'my_school', 'track', 'school', 'all_members'])],
            'track_id' => ['nullable', 'integer', 'exists:tac_tracks,id'],
            'school' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'action_text' => ['nullable', 'string', 'max:100', 'required_with:action_url'],
            'action_url' => ['nullable', 'url', 'max:500', 'required_with:action_text'],
        ]);

        // Executive-only audiences: a mentor or school lead cannot reach past
        // their own scope just by editing the request payload.
        if (in_array($validated['audience'], ['track', 'school', 'all_members'], true) && ! $isExecutive) {
            abort(403, 'Only TAC executives may message outside their own track or school.');
        }

        $recipients = $this->resolveRecipients($validated, $user);

        if ($recipients->isEmpty()) {
            return back()->with('error', 'No members match that audience — nothing was sent.');
        }

        $messageHtml = EmailContentSanitizer::sanitize($validated['message']);

        if ($messageHtml === '') {
            return back()->withErrors(['message' => 'Write a message before sending.'])->withInput();
        }

        Notification::sendNow($recipients, new TacAnnouncementNotification(
            subject: $validated['subject'],
            messageHtml: $messageHtml,
            actionText: $validated['action_text'] ?? null,
            actionUrl: $validated['action_url'] ?? null,
        ));

        $count = $recipients->count();

        return back()->with('success', "Announcement sent to {$count} member(s).");
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return \Illuminate\Support\Collection<int, CommunityMember>
     */
    private function resolveRecipients(array $validated, User $user)
    {
        $base = CommunityMember::query()->mailable();

        return match ($validated['audience']) {
            'my_track' => $base->whereHas(
                'tracks',
                fn ($q) => $q->whereIn('tac_tracks.id', $user->tacManagedTrackIds())
            )->get(),

            'my_school' => $base->whereIn('school', $user->tacManagedSchools())->get(),

            'track' => $validated['track_id']
                ? $base->whereHas('tracks', fn ($q) => $q->where('tac_tracks.id', $validated['track_id']))->get()
                : collect(),

            'school' => $validated['school']
                ? $base->where('school', $validated['school'])->get()
                : collect(),

            'all_members' => $base->get(),

            default => collect(),
        };
    }

    /**
     * Image upload for the announcement composer's rich-text editor. A
     * separate endpoint from the site-wide email-campaign one so that a
     * track mentor or school lead — a plain `user` account with TAC
     * leadership, not a full admin — can embed an image without needing
     * executive access.
     */
    public function uploadMedia(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'media' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $path = $request->file('media')->store('community/announcements', 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
            'path' => $path,
            'name' => $validated['media']->getClientOriginalName(),
        ]);
    }
}
