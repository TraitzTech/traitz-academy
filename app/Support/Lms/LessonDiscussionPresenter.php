<?php

namespace App\Support\Lms;

use App\Models\CourseLesson;
use App\Models\Discussion;
use App\Models\DiscussionUpvote;
use App\Models\User;

class LessonDiscussionPresenter
{
    /**
     * @return array{questions: list<array<string, mixed>>, can_moderate: bool}
     */
    public static function forLesson(CourseLesson $lesson, ?User $viewer, bool $canModerate): array
    {
        $viewerId = $viewer?->id;

        $roots = Discussion::query()
            ->forLesson($lesson->id)
            ->topLevel()
            ->with([
                'user:id,name',
                'replies' => fn ($q) => $q->with('user:id,name')->orderByDesc('is_accepted_answer')->orderBy('created_at'),
            ])
            ->orderByDesc('upvotes_count')
            ->orderByDesc('created_at')
            ->get();

        $rootIds = $roots->pluck('id')->all();
        $upvotedIds = [];
        if ($viewerId !== null && $rootIds !== []) {
            $upvotedIds = DiscussionUpvote::query()
                ->where('user_id', $viewerId)
                ->whereIn('discussion_id', $rootIds)
                ->pluck('discussion_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $questions = $roots->map(function (Discussion $q) use ($viewerId, $upvotedIds, $canModerate) {
            return [
                'id' => $q->id,
                'body' => $q->body,
                'user' => [
                    'id' => $q->user_id,
                    'name' => $q->user?->name ?? 'Unknown',
                ],
                'upvotes_count' => (int) $q->upvotes_count,
                'user_has_upvoted' => in_array((int) $q->id, $upvotedIds, true),
                'can_delete' => $viewerId !== null && ((int) $q->user_id === (int) $viewerId || $canModerate),
                'created_at' => $q->created_at?->toIso8601String(),
                'replies' => $q->replies->map(function (Discussion $r) use ($viewerId, $canModerate) {
                    return [
                        'id' => $r->id,
                        'body' => $r->body,
                        'is_accepted_answer' => (bool) $r->is_accepted_answer,
                        'user' => [
                            'id' => $r->user_id,
                            'name' => $r->user?->name ?? 'Unknown',
                        ],
                        'can_delete' => $viewerId !== null && ((int) $r->user_id === (int) $viewerId || $canModerate),
                        'can_accept' => $canModerate,
                        'created_at' => $r->created_at?->toIso8601String(),
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return [
            'questions' => $questions,
            'can_moderate' => $canModerate,
        ];
    }
}
