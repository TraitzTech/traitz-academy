<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $applications = $user->applications()->with('program')->latest()->get();
        $registrations = $user->registrations()->with('event')->latest()->get();

        return Inertia::render('Dashboard', [
            'user' => $user,
            'applications' => $applications,
            'registrations' => $registrations,
        ]);
    }
}
