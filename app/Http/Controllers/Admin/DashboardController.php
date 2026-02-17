<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Program;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_programs' => Program::count(),
            'total_events' => Event::count(),
            'pending_applications' => Application::where('status', 'pending')->count(),
            'total_users' => User::where('role', '!=', 'admin')->count(),
            'total_collected' => (float) Payment::where('status', 'successful')->sum('amount'),
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
