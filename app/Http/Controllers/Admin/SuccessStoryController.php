<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SuccessStory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SuccessStoryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SuccessStory::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('company', 'like', "%{$request->search}%")
                    ->orWhere('role', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $stories = $query->ordered()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/SuccessStories/Index', [
            'stories' => $stories,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/SuccessStories/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'story' => 'required|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')->store('success-stories', 'public');
        }

        unset($validated['image']);

        SuccessStory::create($validated);

        return redirect()->route('admin.success-stories.index')
            ->with('success', 'Success story created successfully.');
    }

    public function edit(SuccessStory $successStory): Response
    {
        return Inertia::render('Admin/SuccessStories/Edit', [
            'story' => $successStory,
        ]);
    }

    public function update(Request $request, SuccessStory $successStory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'story' => 'required|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($successStory->image_url && Storage::disk('public')->exists($successStory->image_url)) {
                Storage::disk('public')->delete($successStory->image_url);
            }
            $validated['image_url'] = $request->file('image')->store('success-stories', 'public');
        }

        unset($validated['image']);

        $successStory->update($validated);

        return redirect()->route('admin.success-stories.index')
            ->with('success', 'Success story updated successfully.');
    }

    public function destroy(SuccessStory $successStory): RedirectResponse
    {
        // Delete image if exists
        if ($successStory->image_url && Storage::disk('public')->exists($successStory->image_url)) {
            Storage::disk('public')->delete($successStory->image_url);
        }

        $successStory->delete();

        return redirect()->route('admin.success-stories.index')
            ->with('success', 'Success story deleted successfully.');
    }

    public function toggleStatus(SuccessStory $successStory): RedirectResponse
    {
        $successStory->update([
            'is_active' => ! $successStory->is_active,
        ]);

        return back()->with('success', 'Status updated successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:success_stories,id',
        ]);

        $stories = SuccessStory::whereIn('id', $validated['ids'])->get();

        foreach ($stories as $story) {
            if ($story->image_url && Storage::disk('public')->exists($story->image_url)) {
                Storage::disk('public')->delete($story->image_url);
            }
            $story->delete();
        }

        return back()->with('success', count($validated['ids']).' success stories deleted successfully.');
    }
}
