<?php

namespace App\Http\Controllers\Internships;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LogbookMediaController extends Controller
{
    /**
     * Upload a screenshot/image for a logbook entry's rich-text content.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'media' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $path = $request->file('media')->store('logbook-content', 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
            'path' => $path,
        ]);
    }
}
