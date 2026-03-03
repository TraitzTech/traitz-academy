<?php

namespace App\Http\Controllers;

use App\Models\AiForgeEvent;
use App\Models\AiForgeSwag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiForgeSwagController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $event = AiForgeEvent::query()
            ->where('is_active', true)
            ->where('swag_store_active', true)
            ->first();

        if (! $event) {
            return redirect()->route('home')->with('info', 'The AI Forge swag store is not available at this time.');
        }

        $query = AiForgeSwag::query()
            ->where('ai_forge_event_id', $event->id)
            ->where('is_active', true);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $swags = $query->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $categories = AiForgeSwag::query()
            ->where('ai_forge_event_id', $event->id)
            ->where('is_active', true)
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        return Inertia::render('AiForge/Swags/Index', [
            'event' => $event,
            'swags' => $swags,
            'categories' => $categories,
            'filters' => $request->only(['category', 'search']),
        ]);
    }

    public function show(AiForgeSwag $swag): Response|RedirectResponse
    {
        if (! $swag->is_active) {
            return redirect()->route('ai-forge.swags.index')->with('error', 'This swag item is no longer available.');
        }

        $relatedSwags = AiForgeSwag::query()
            ->where('ai_forge_event_id', $swag->ai_forge_event_id)
            ->where('is_active', true)
            ->where('id', '!=', $swag->id)
            ->where('category', $swag->category)
            ->limit(4)
            ->get();

        return Inertia::render('AiForge/Swags/Show', [
            'swag' => $swag,
            'relatedSwags' => $relatedSwags,
        ]);
    }
}
