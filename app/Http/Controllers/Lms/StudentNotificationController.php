<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentNotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->limit(60)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'type' => class_basename($notification->type),
                'data' => $notification->data,
                'read_at' => optional($notification->read_at)?->toIso8601String(),
                'created_at' => optional($notification->created_at)?->toIso8601String(),
            ])
            ->values();

        return Inertia::render('Lms/Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()?->unreadNotifications->markAsRead();

        return back()->with('success', 'Notifications marked as read.');
    }
}

