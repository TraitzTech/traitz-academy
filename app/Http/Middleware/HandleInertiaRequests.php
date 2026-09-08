<?php

namespace App\Http\Middleware;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                'warning' => fn () => $request->session()->get('warning'),
                'status' => fn () => $request->session()->get('status'),
            ],
            'cartCount' => fn () => collect($request->session()->get('ai_forge_cart', []))->sum('quantity'),
            'pendingCoursesCount' => fn () => $request->user()?->role && in_array($request->user()->role, ['cto', 'ceo', 'program_coordinator', 'admin'])
                ? Course::where('status', 'pending_review')->count()
                : null,
            'internshipAccess' => function () use ($request) {
                $user = $request->user();
                if (! $user || ! $this->tableExists('internships')) {
                    return ['is_intern' => false, 'supervises' => false];
                }

                return [
                    'is_intern' => \App\Models\Internship::query()
                        ->where('user_id', $user->id)
                        ->where('status', 'active')
                        ->exists(),
                    'supervises' => $user->canAccessAdminPanel()
                        || \App\Models\Internship::query()->forSupervisor($user)->exists(),
                ];
            },
            // TAC leadership is a capability, not a role: a track mentor or
            // school lead may be a plain `user` account and still need the
            // community admin in their sidebar.
            'tacAccess' => function () use ($request) {
                $user = $request->user();

                if (! $user || ! $this->tableExists('tac_leaders')) {
                    return ['can_admin' => false, 'is_executive' => false, 'is_partnership_lead' => false];
                }

                return [
                    'can_admin' => $user->canAccessTacAdmin(),
                    'is_executive' => $user->hasTacExecutiveAuthority(),
                    'is_partnership_lead' => $user->isTacPartnershipLead(),
                ];
            },

            // Powers the "Member Area" nav link: only shown to signed-in
            // users who actually have a TAC membership record (most people
            // pick one up automatically the moment they register for
            // anything — see CommunityEnrollmentService).
            'communityMembership' => function () use ($request) {
                $user = $request->user();

                if (! $user || ! $this->tableExists('community_members')) {
                    return ['is_member' => false];
                }

                return [
                    'is_member' => \App\Models\CommunityMember::query()
                        ->where('user_id', $user->id)
                        ->orWhere('email', mb_strtolower($user->email))
                        ->exists(),
                ];
            },

            'unreadNotificationsCount' => function () use ($request) {
                $user = $request->user();
                if (! $user) {
                    return 0;
                }
                if (! $this->tableExists('notifications')) {
                    return 0;
                }

                return (int) $user->unreadNotifications()->count();
            },
        ];
    }

    /**
     * Cached table-existence check. The schema is stable for a running app,
     * so this avoids a slow information_schema query on every request.
     */
    protected function tableExists(string $table): bool
    {
        return \Illuminate\Support\Facades\Cache::remember(
            "schema.has_table.{$table}",
            now()->addDay(),
            fn () => Schema::hasTable($table),
        );
    }
}
