<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonContentMediaController extends Controller
{
    /**
     * Upload an image for rich-text lesson content (tutors and staff with admin panel access).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user && ($user->isTutor() || $user->canAccessAdminPanel()),
            403
        );

        $request->validate([
            'media' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $path = $request->file('media')->store('lesson-content', 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
            'path' => $path,
        ]);
    }
}
