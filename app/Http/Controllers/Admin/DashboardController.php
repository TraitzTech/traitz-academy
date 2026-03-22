<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $authUser = $request->user();

        $adminRoles = [
            User::ROLE_CTO,
            User::ROLE_CEO,
            User::ROLE_PROGRAM_COORDINATOR,
            User::ROLE_ADMIN_LEGACY,
        ];

        $totalCollectedQuery = Payment::query()->where('status', 'successful');

        if ($authUser->isProgramCoordinator()) {
            $totalCollectedQuery->where('manual_entry', true)
                ->where(function ($query) use ($authUser) {
                    $query->where('recorded_by', $authUser->id)
                        ->orWhere(function ($fallbackQuery) use ($authUser) {
                            $fallbackQuery->whereNull('recorded_by')
                                ->where('updated_by', $authUser->id);
                        });
                });
        }

        $stats = [
            'total_programs' => Program::count(),
            'total_events' => Event::count(),
            'pending_applications' => Application::where('status', 'pending')->count(),
            'total_users' => User::whereNotIn('role', $adminRoles)->count(),
            'total_collected' => (float) $totalCollectedQuery->sum('amount'),
            'collected_label' => $authUser->isProgramCoordinator() ? 'My Collected' : 'Total Collected',
        ];

        $recentApplications = Application::with('program')
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentApplications' => $recentApplications,
        ]);
    }
}
