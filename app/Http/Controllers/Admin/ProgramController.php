<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProgramController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Program::query();

        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $programs = $query->withCount('applications')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Programs/Index', [
            'programs' => $programs,
            'filters' => $request->only(['search', 'category', 'status']),
            'categories' => [
                'professional-training' => 'Professional Training',
                'bootcamp' => 'Bootcamp',
                'workshop' => 'Workshop',
                'academic-internship' => 'Academic Internship',
                'professional-internship' => 'Professional Internship',
                'job-opportunity' => 'Job Opportunity',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Programs/Create', [
            'categories' => [
                'professional-training' => 'Professional Training',
                'bootcamp' => 'Bootcamp',
                'workshop' => 'Workshop',
                'academic-internship' => 'Academic Internship',
                'professional-internship' => 'Professional Internship',
                'job-opportunity' => 'Job Opportunity',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:professional-training,bootcamp,workshop,academic-internship,professional-internship,job-opportunity',
            'description' => 'required|string',
            'overview' => 'nullable|string',
            'who_is_for' => 'nullable|string',
            'skills_and_tools' => 'nullable|string',
            'duration' => 'required|string|max:100',
            'learning_outcomes' => 'nullable|string',
            'certification' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'max_installments' => 'required|integer|min:1|max:12',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'capacity' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'curriculum' => 'nullable|string',
        ]);

        $slug = Str::slug($validated['title']);

        // Check if slug+category combination already exists
        $existingProgram = Program::where('slug', $slug)
            ->where('category', $validated['category'])
            ->first();

        if ($existingProgram) {
            return back()->withErrors([
                'title' => "A program with this title already exists in the \"{$validated['category']}\" category. Please use a different title or category.",
            ])->withInput();
        }

        $validated['slug'] = $slug;

        if ((float) $validated['price'] <= 0) {
            $validated['max_installments'] = 1;
        }

        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')->store('programs', 'public');
        }

        unset($validated['image']);

        Program::create($validated);

        return redirect()->route('admin.programs.index')
            ->with('success', 'Program created successfully.');
    }

    public function edit(Program $program): Response
    {
        return Inertia::render('Admin/Programs/Edit', [
            'program' => $program,
            'categories' => [
                'professional-training' => 'Professional Training',
                'bootcamp' => 'Bootcamp',
                'workshop' => 'Workshop',
                'academic-internship' => 'Academic Internship',
                'professional-internship' => 'Professional Internship',
                'job-opportunity' => 'Job Opportunity',
            ],
        ]);
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:professional-training,bootcamp,workshop,academic-internship,professional-internship,job-opportunity',
            'description' => 'required|string',
            'overview' => 'nullable|string',
            'who_is_for' => 'nullable|string',
            'skills_and_tools' => 'nullable|string',
            'duration' => 'required|string|max:100',
            'learning_outcomes' => 'nullable|string',
            'certification' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'max_installments' => 'required|integer|min:1|max:12',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'capacity' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'curriculum' => 'nullable|string',
        ]);

        if (isset($validated['title'])) {
            $slug = Str::slug($validated['title']);

            // Check if slug+category combination already exists (excluding current program)
            $existingProgram = Program::where('slug', $slug)
                ->where('category', $validated['category'])
                ->where('id', '!=', $program->id)
                ->first();

            if ($existingProgram) {
                return back()->withErrors([
                    'title' => "A program with this title already exists in the \"{$validated['category']}\" category. Please use a different title or category.",
                ])->withInput();
            }

            $validated['slug'] = $slug;
        }

        if ((float) $validated['price'] <= 0) {
            $validated['max_installments'] = 1;
        }

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($program->image_url && Storage::disk('public')->exists($program->image_url)) {
                Storage::disk('public')->delete($program->image_url);
            }
            $validated['image_url'] = $request->file('image')->store('programs', 'public');
        }

        unset($validated['image']);

        $program->update($validated);

        return redirect()->route('admin.programs.index')
            ->with('success', 'Program updated successfully.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        // Delete image if exists
        if ($program->image_url && Storage::disk('public')->exists($program->image_url)) {
            Storage::disk('public')->delete($program->image_url);
        }

        $program->delete();

        return redirect()->route('admin.programs.index')
            ->with('success', 'Program deleted successfully.');
    }

    public function toggleStatus(Program $program): RedirectResponse
    {
        $program->update(['is_active' => ! $program->is_active]);

        return back()->with('success', 'Program status updated.');
    }

    public function toggleFeatured(Program $program): RedirectResponse
    {
        $program->update(['is_featured' => ! $program->is_featured]);

        return back()->with('success', 'Program featured status updated.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:programs,id',
        ]);

        $programs = Program::whereIn('id', $validated['ids'])->get();

        foreach ($programs as $program) {
            if ($program->image_url && Storage::disk('public')->exists($program->image_url)) {
                Storage::disk('public')->delete($program->image_url);
            }
            $program->delete();
        }

        $count = count($validated['ids']);

        return back()->with('success', "{$count} program(s) deleted successfully.");
    }
}
