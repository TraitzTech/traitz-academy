<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiForgeEvent;
use App\Models\AiForgeOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiForgeOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $event = AiForgeEvent::query()->first();

        $query = AiForgeOrder::query()->with('items.swag');

        if ($event) {
            $query->where('ai_forge_event_id', $event->id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        $orders = $query->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => AiForgeOrder::query()->where('ai_forge_event_id', $event?->id)->count(),
            'completed' => AiForgeOrder::query()->where('ai_forge_event_id', $event?->id)->where('payment_status', 'paid')->count(),
            'pending' => AiForgeOrder::query()->where('ai_forge_event_id', $event?->id)->where('payment_status', 'pending')->count(),
            'failed' => AiForgeOrder::query()->where('ai_forge_event_id', $event?->id)->where('payment_status', 'failed')->count(),
            'totalRevenue' => (int) AiForgeOrder::query()->where('ai_forge_event_id', $event?->id)->where('payment_status', 'paid')->sum('total_amount'),
        ];

        return Inertia::render('Admin/AiForge/Orders/Index', [
            'event' => $event ? ['id' => $event->id, 'title' => $event->title] : null,
            'orders' => $orders,
            'filters' => $request->only(['search', 'status']),
            'stats' => $stats,
        ]);
    }

    public function show(AiForgeOrder $order): Response
    {
        $order->load(['items.swag', 'user']);
        $event = AiForgeEvent::query()->first();

        return Inertia::render('Admin/AiForge/Orders/Show', [
            'event' => $event ? ['id' => $event->id, 'title' => $event->title] : null,
            'order' => $order,
        ]);
    }

    public function updateStatus(Request $request, AiForgeOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,confirmed,processing,shipped,delivered,cancelled'],
        ]);

        $order->update(['status' => $validated['status']]);

        return back()->with('success', 'Order status updated.');
    }
}
