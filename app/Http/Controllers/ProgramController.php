<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Inertia\Inertia;

class ProgramController extends Controller
{
    public function index(): \Inertia\Response
    {
        $programs = Program::where('is_active', true)
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Programs/Index', [
            'programs' => $programs,
        ]);
    }

    public function show(Program $program): \Inertia\Response
    {
        $userApplication = null;
        if (auth()->check()) {
            $userApplication = $program->applications()
                ->where('user_id', auth()->id())
                ->first();
        }

        return Inertia::render('Programs/Show', [
            'program' => $program,
            'userApplication' => $userApplication,
        ]);
    }
}
