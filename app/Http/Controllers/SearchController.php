<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Get search options for programs and events (for homepage search).
     */
    public function options(): JsonResponse
    {
        $programCategories = Program::where('is_active', true)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        $eventCategories = Event::where('is_active', true)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        $durations = Program::where('is_active', true)
            ->distinct()
            ->pluck('duration')
            ->filter()
            ->values();

        $priceRanges = [
            ['label' => 'Free', 'min' => 0, 'max' => 0],
            ['label' => 'Under 50,000 XAF', 'min' => 1, 'max' => 50000],
            ['label' => '50,000 - 100,000 XAF', 'min' => 50000, 'max' => 100000],
            ['label' => '100,000 - 200,000 XAF', 'min' => 100000, 'max' => 200000],
            ['label' => 'Over 200,000 XAF', 'min' => 200000, 'max' => null],
        ];

        return response()->json([
            'programCategories' => $programCategories,
            'eventCategories' => $eventCategories,
            'durations' => $durations,
            'priceRanges' => $priceRanges,
        ]);
    }

    /**
     * Search programs with filters.
     */
    public function programs(Request $request): JsonResponse
    {
        $query = Program::where('is_active', true);

        // Text search across multiple fields
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('overview', 'like', "%{$search}%")
                    ->orWhere('who_is_for', 'like', "%{$search}%")
                    ->orWhere('skills_and_tools', 'like', "%{$search}%")
                    ->orWhere('learning_outcomes', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('category', $request->input('category'));
        }

        // Duration filter
        if ($request->filled('duration')) {
            $query->where('duration', $request->input('duration'));
        }

        // Price range filter
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->input('price_min'));
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->input('price_max'));
        }

        // Slots available filter
        if ($request->boolean('has_slots')) {
            $query->whereRaw('capacity > enrolled_count');
        }

        // Featured filter
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        // Starting soon filter (within next 30 days)
        if ($request->boolean('starting_soon')) {
            $query->whereNotNull('start_date')
                ->where('start_date', '>=', now())
                ->where('start_date', '<=', now()->addDays(30));
        }

        $programs = $query->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'programs' => $programs,
            'count' => $programs->count(),
        ]);
    }

    /**
     * Search events with filters.
     */
    public function events(Request $request): JsonResponse
    {
        $query = Event::where('is_active', true);

        // Text search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('agenda', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('category', $request->input('category'));
        }

        // Online/In-person filter
        if ($request->filled('event_type')) {
            if ($request->input('event_type') === 'online') {
                $query->where('is_online', true);
            } elseif ($request->input('event_type') === 'in-person') {
                $query->where('is_online', false);
            }
        }

        // Location filter
        if ($request->filled('location')) {
            $query->where('location', 'like', "%{$request->input('location')}%");
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('event_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('event_date', '<=', $request->input('date_to'));
        }

        // Upcoming only filter
        if ($request->boolean('upcoming')) {
            $query->where('event_date', '>=', now());
        }

        // Slots available filter
        if ($request->boolean('has_slots')) {
            $query->whereRaw('capacity > registered_count');
        }

        $events = $query->orderBy('event_date')
            ->get();

        return response()->json([
            'events' => $events,
            'count' => $events->count(),
        ]);
    }
}
