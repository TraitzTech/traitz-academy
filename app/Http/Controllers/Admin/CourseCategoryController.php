<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CourseCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = CourseCategory::query()
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->withCount('courses')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/CourseCategories/Index', [
            'categories' => $categories,
            'filters'    => $request->only(['search', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:course_categories,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon'        => ['nullable', 'string', 'max:10'],
            'color'       => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['boolean'],
        ]);

        $validated['slug'] = $this->uniqueSlug($validated['name']);

        CourseCategory::create($validated);

        return back()->with('success', "Category \"{$validated['name']}\" created.");
    }

    public function update(Request $request, CourseCategory $courseCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100', "unique:course_categories,name,{$courseCategory->id}"],
            'description' => ['nullable', 'string', 'max:500'],
            'icon'        => ['nullable', 'string', 'max:10'],
            'color'       => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['boolean'],
        ]);

        if ($validated['name'] !== $courseCategory->name) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $courseCategory->id);
        }

        $courseCategory->update($validated);

        return back()->with('success', "Category updated.");
    }

    public function destroy(CourseCategory $courseCategory): RedirectResponse
    {
        if ($courseCategory->courses()->count() > 0) {
            return back()->with('error', "Cannot delete \"{$courseCategory->name}\" — it has {$courseCategory->courses()->count()} course(s) assigned to it.");
        }

        $courseCategory->delete();

        return back()->with('success', "Category \"{$courseCategory->name}\" deleted.");
    }

    public function toggleActive(CourseCategory $courseCategory): RedirectResponse
    {
        $courseCategory->update(['is_active' => ! $courseCategory->is_active]);

        $status = $courseCategory->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Category \"{$courseCategory->name}\" {$status}.");
    }

    private function uniqueSlug(string $name, ?int $excludeId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;

        while (
            CourseCategory::where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
