<?php

namespace App\Http\Controllers\Lms;

use App\Events\LiveClassMessageSent;
use App\Http\Controllers\Controller;
use App\Models\LiveClass;
use App\Models\LiveClassAttendance;
use Firebase\JWT\JWT;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LiveClassController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $classes = LiveClass::query()
            ->with(['tutor:id,name', 'courses:id,title'])
            ->latest('start_time')
            ->get()
            ->filter(fn (LiveClass $liveClass) => $liveClass->canUserJoin($user))
            ->map(fn (LiveClass $liveClass) => [
                'id' => $liveClass->id,
                'title' => $liveClass->title,
                'start_time' => optional($liveClass->start_time)->toIso8601String(),
                'duration' => $liveClass->duration,
                'tutor' => $liveClass->tutor,
                'host_online' => $liveClass->hasHostOnline(),
            ])
            ->values();

        return Inertia::render('Lms/LiveClasses/Index', [
            'classes' => $classes,
            'now' => now()->toIso8601String(),
        ]);
    }

    public function recordings(Request $request): Response
    {
        $user = $request->user();

        $classes = LiveClass::query()
            ->with([
                'tutor:id,name',
                'recordings' => fn ($query) => $query
                    ->where(function ($q) {
                        $q->whereNotNull('youtube_url')
                            ->orWhereNotNull('file_path');
                    })
                    ->latest('created_at'),
            ])
            ->latest('start_time')
            ->get()
            ->filter(fn (LiveClass $liveClass) => $liveClass->canUserJoin($user))
            ->map(function (LiveClass $liveClass) {
                return [
                    'id' => $liveClass->id,
                    'title' => $liveClass->title,
                    'start_time' => optional($liveClass->start_time)->toIso8601String(),
                    'duration' => $liveClass->duration,
                    'tutor' => $liveClass->tutor,
                    'recordings' => $liveClass->recordings->map(fn ($recording) => [
                        'id' => $recording->id,
                        'file_path' => $recording->file_path,
                        'youtube_url' => $recording->youtube_url,
                        'status' => $recording->status,
                        'created_at' => optional($recording->created_at)->toIso8601String(),
                    ])->values(),
                ];
            })
            ->filter(fn (array $row) => count($row['recordings']) > 0)
            ->values();

        return Inertia::render('Lms/LiveClasses/Recordings', [
            'classes' => $classes,
        ]);
    }

    public function show(Request $request, LiveClass $liveClass): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($liveClass->canUserJoin($user), 403);
        if ($user->role === 'user' && ! $this->studentCanEnterRoom($liveClass)) {
            return redirect()
                ->route('lms.live-classes.details', $liveClass)
                ->with('warning', 'You can join only when the class is open and a tutor/admin is already in the room.');
        }

        $liveClass->load([
            'tutor:id,name',
            'recordings',
            'messages.user:id,name,role',
            'attendance.student:id,name,email',
        ]);

        return Inertia::render('Lms/LiveClasses/Room', [
            'liveClass' => [
                'id' => $liveClass->id,
                'title' => $liveClass->title,
                'description' => $liveClass->description,
                'room_name' => $liveClass->room_name,
                'start_time' => optional($liveClass->start_time)->toIso8601String(),
                'duration' => $liveClass->duration,
                'tutor' => $liveClass->tutor,
                'jitsi' => [
                    'domain' => config('services.jitsi.domain', 'meet.jit.si'),
                    'room_name' => $this->resolveJitsiRoomName($liveClass),
                    'jwt' => $this->issueJitsiJwt($liveClass, $user),
                ],
                'recordings' => $liveClass->recordings,
                'messages' => $liveClass->messages->take(100)->reverse()->values()->map(fn ($msg) => [
                    'id' => $msg->id,
                    'user_name' => $msg->user?->name,
                    'user_role' => $msg->user?->role,
                    'message' => $msg->message,
                    'created_at' => optional($msg->created_at)->toIso8601String(),
                ]),
                'attendance' => $liveClass->attendance->map(fn ($row) => [
                    'id' => $row->id,
                    'student_name' => $row->student?->name,
                    'joined_at' => optional($row->joined_at)->toIso8601String(),
                    'left_at' => optional($row->left_at)->toIso8601String(),
                    'duration_minutes' => $row->duration_minutes,
                ])->values(),
            ],
            'authUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
            'canManage' => $user->canAccessAdminPanel() || (int) $liveClass->tutor_id === (int) $user->id,
            'manageRedirectUrl' => $user->canAccessAdminPanel()
                ? route('admin.lms.live-classes.show', $liveClass)
                : ((int) $liveClass->tutor_id === (int) $user->id ? route('tutor.live-classes.show', $liveClass) : null),
        ]);
    }

    public function details(Request $request, LiveClass $liveClass): Response
    {
        $user = $request->user();
        abort_unless($liveClass->canUserJoin($user), 403);

        $liveClass->load('tutor:id,name', 'recordings');

        return Inertia::render('Lms/LiveClasses/Details', [
            'liveClass' => [
                'id' => $liveClass->id,
                'title' => $liveClass->title,
                'description' => $liveClass->description,
                'start_time' => optional($liveClass->start_time)->toIso8601String(),
                'end_time' => optional($liveClass->endsAt())->toIso8601String(),
                'duration' => $liveClass->duration,
                'tutor' => $liveClass->tutor,
                'recordings' => $liveClass->recordings->map(fn ($recording) => [
                    'id' => $recording->id,
                    'youtube_url' => $recording->youtube_url,
                    'status' => $recording->status,
                    'created_at' => optional($recording->created_at)->toIso8601String(),
                ])->values(),
            ],
            'now' => now()->toIso8601String(),
            'canJoinNow' => $user->role !== 'user' || $this->studentCanEnterRoom($liveClass),
            'joinOpensAt' => optional($liveClass->studentJoinOpensAt())->toIso8601String(),
            'hostOnline' => $liveClass->hasHostOnline(),
        ]);
    }

    public function messages(Request $request, LiveClass $liveClass): JsonResponse
    {
        $user = $request->user();
        abort_unless($liveClass->canUserJoin($user), 403);
        if ($user->role === 'user') {
            abort_unless($this->studentCanEnterRoom($liveClass), 403);
        }

        $messages = $liveClass->messages()
            ->with('user:id,name,role')
            ->limit(100)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($msg) => [
                'id' => $msg->id,
                'user_name' => $msg->user?->name,
                'user_role' => $msg->user?->role,
                'message' => $msg->message,
                'created_at' => optional($msg->created_at)->toIso8601String(),
            ]);

        return response()->json(['messages' => $messages]);
    }

    public function sendMessage(Request $request, LiveClass $liveClass): JsonResponse
    {
        $user = $request->user();
        abort_unless($liveClass->canUserJoin($user), 403);
        if ($user->role === 'user') {
            abort_unless($this->studentCanEnterRoom($liveClass), 403);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $msg = $liveClass->messages()->create([
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ])->load('user:id,name,role');

        broadcast(new LiveClassMessageSent($liveClass, $msg))->toOthers();

        return response()->json([
            'message' => [
                'id' => $msg->id,
                'user_name' => $msg->user?->name,
                'user_role' => $msg->user?->role,
                'message' => $msg->message,
                'created_at' => optional($msg->created_at)->toIso8601String(),
            ],
        ]);
    }

    public function join(Request $request, LiveClass $liveClass): JsonResponse
    {
        $user = $request->user();
        abort_unless($liveClass->canUserJoin($user), 403);

        if ($user->role !== 'user') {
            $liveClass->markHostOnline();
            return response()->json(['ok' => true]);
        }
        abort_unless($this->studentCanEnterRoom($liveClass), 403);

        LiveClassAttendance::query()->create([
            'live_class_id' => $liveClass->id,
            'student_id' => $user->id,
            'joined_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function ping(Request $request, LiveClass $liveClass): JsonResponse
    {
        $user = $request->user();
        abort_unless($liveClass->canUserJoin($user), 403);

        if ($user->role !== 'user') {
            $liveClass->markHostOnline();
            return response()->json(['ok' => true]);
        }
        abort_unless($this->studentCanEnterRoom($liveClass), 403);

        $attendance = LiveClassAttendance::query()
            ->where('live_class_id', $liveClass->id)
            ->where('student_id', $user->id)
            ->latest('joined_at')
            ->first();

        if (! $attendance) {
            $attendance = LiveClassAttendance::query()->create([
                'live_class_id' => $liveClass->id,
                'student_id' => $user->id,
                'joined_at' => now(),
            ]);
        }

        $minutes = max(1, (int) round($attendance->joined_at->diffInMinutes(now())));
        $attendance->update(['duration_minutes' => $minutes]);

        return response()->json(['ok' => true]);
    }

    public function leave(Request $request, LiveClass $liveClass): JsonResponse
    {
        $user = $request->user();
        abort_unless($liveClass->canUserJoin($user), 403);

        if ($user->role !== 'user') {
            $liveClass->clearHostOnline();
            return response()->json(['ok' => true]);
        }

        $attendance = LiveClassAttendance::query()
            ->where('live_class_id', $liveClass->id)
            ->where('student_id', $user->id)
            ->latest('joined_at')
            ->first();

        if ($attendance) {
            $leftAt = now();
            $attendance->update([
                'left_at' => $leftAt,
                'duration_minutes' => max(1, (int) round($attendance->joined_at->diffInMinutes($leftAt))),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    private function studentCanEnterRoom(LiveClass $liveClass): bool
    {
        return $liveClass->canStudentJoinNow() && $liveClass->hasHostOnline();
    }

    private function issueJitsiJwt(LiveClass $liveClass, $user): ?string
    {
        $appId = (string) config('services.jitsi.app_id');
        $domain = (string) config('services.jitsi.domain', 'meet.jit.si');

        if ($appId === '' || $domain === '' || $domain === 'meet.jit.si') {
            return null;
        }

        $isModerator = $user->canAccessAdminPanel() || (int) $liveClass->tutor_id === (int) $user->id;
        $now = time();

        $roomName = $this->resolveJitsiRoomName($liveClass);
        $baseRoomName = $liveClass->room_name;

        $basePayload = [
            'aud' => 'jitsi',
            'exp' => $now + 7200,
            'nbf' => $now - 10,
            'iat' => $now,
            'jti' => (string) Str::uuid(),
            'room' => $roomName,
            'context' => [
                'user' => [
                    'id' => (string) $user->id,
                    'name' => (string) $user->name,
                    'email' => (string) ($user->email ?? ''),
                    'moderator' => $isModerator ? 'true' : 'false',
                ],
            ],
            'moderator' => $isModerator,
        ];

        if ($this->isJaasDomain($domain)) {
            $privateKey = $this->resolveJitsiPrivateKey();
            $keyId = (string) config('services.jitsi.key_id');

            if ($privateKey === '' || $keyId === '') {
                return null;
            }

            $payload = array_merge($basePayload, [
                'room' => $baseRoomName,
                'iss' => 'chat',
                'sub' => $appId,
            ]);

            return JWT::encode($payload, $privateKey, 'RS256', $keyId);
        }

        $appSecret = (string) config('services.jitsi.app_secret');
        if ($appSecret === '') {
            return null;
        }

        $payload = array_merge($basePayload, [
            'iss' => $appId,
            'sub' => $domain,
        ]);

        return JWT::encode($payload, $appSecret, 'HS256');
    }

    private function resolveJitsiRoomName(LiveClass $liveClass): string
    {
        $baseRoomName = $liveClass->room_name;
        $domain = (string) config('services.jitsi.domain', 'meet.jit.si');
        $appId = (string) config('services.jitsi.app_id');

        if ($this->isJaasDomain($domain) && $appId !== '' && ! str_contains($baseRoomName, '/')) {
            return $appId.'/'.$baseRoomName;
        }

        return $baseRoomName;
    }

    private function resolveJitsiPrivateKey(): string
    {
        $raw = (string) config('services.jitsi.private_key', '');
        if ($raw !== '') {
            return str_replace('\n', PHP_EOL, trim($raw));
        }

        $path = (string) config('services.jitsi.private_key_path', '');
        if ($path !== '') {
            $candidates = [$path];
            if (! str_starts_with($path, '/')) {
                $candidates[] = base_path($path);
            }

            foreach ($candidates as $candidate) {
                if (File::exists($candidate)) {
                    return (string) File::get($candidate);
                }
            }
        }

        return '';
    }

    private function isJaasDomain(string $domain): bool
    {
        return str_contains($domain, '8x8.vc');
    }
}
