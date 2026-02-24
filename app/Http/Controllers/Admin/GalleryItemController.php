<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GalleryItemController extends Controller
{
    public function index(Request $request): Response
    {
        $query = GalleryItem::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->toString() === 'active');
        }

        if ($request->filled('tag')) {
            $tag = strtolower($request->string('tag')->toString());
            $query->whereJsonContains('tags', $tag);
        }

        $items = $query->ordered()->paginate(12)->withQueryString();

        return Inertia::render('Admin/Gallery/Index', [
            'items' => $items,
            'filters' => $request->only(['search', 'type', 'status', 'tag']),
            'types' => [
                'image' => 'Image',
                'youtube_video' => 'YouTube Video',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Gallery/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:image,youtube_video'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:4096'],
            'youtube_url' => ['nullable', 'url', 'max:500'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        if ($validated['type'] === 'image' && ! $request->hasFile('image')) {
            return back()->withErrors(['image' => 'An image file is required for image gallery items.'])->withInput();
        }

        if ($validated['type'] === 'youtube_video' && empty($validated['youtube_url'])) {
            return back()->withErrors(['youtube_url' => 'A YouTube link is required for video gallery items.'])->withInput();
        }

        if ($validated['type'] === 'youtube_video' && ! $this->isYouTubeUrl((string) $validated['youtube_url'])) {
            return back()->withErrors(['youtube_url' => 'Please provide a valid YouTube URL.'])->withInput();
        }

        $slug = $this->generateUniqueSlug((string) $validated['title']);

        $payload = [
            'title' => $validated['title'],
            'slug' => $slug,
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'youtube_url' => $validated['type'] === 'youtube_video' ? ($validated['youtube_url'] ?? null) : null,
            'tags' => $this->normalizeTags($validated['tags'] ?? null),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'published_at' => now(),
        ];

        if ($validated['type'] === 'image' && $request->hasFile('image')) {
            $payload['image_path'] = $request->file('image')->store('gallery', 'public');
        }

        GalleryItem::create($payload);

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery item created successfully.');
    }

    public function edit(GalleryItem $gallery): Response
    {
        return Inertia::render('Admin/Gallery/Edit', [
            'item' => $gallery,
            'tagsText' => implode(', ', $gallery->tags ?? []),
        ]);
    }

    public function update(Request $request, GalleryItem $gallery)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:image,youtube_video'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:4096'],
            'youtube_url' => ['nullable', 'url', 'max:500'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        if ($validated['type'] === 'image' && ! $request->hasFile('image') && empty($gallery->image_path)) {
            return back()->withErrors(['image' => 'An image file is required for image gallery items.'])->withInput();
        }

        if ($validated['type'] === 'youtube_video' && empty($validated['youtube_url'])) {
            return back()->withErrors(['youtube_url' => 'A YouTube link is required for video gallery items.'])->withInput();
        }

        if ($validated['type'] === 'youtube_video' && ! $this->isYouTubeUrl((string) $validated['youtube_url'])) {
            return back()->withErrors(['youtube_url' => 'Please provide a valid YouTube URL.'])->withInput();
        }

        $payload = [
            'title' => $validated['title'],
            'slug' => $validated['title'] === $gallery->title ? $gallery->slug : $this->generateUniqueSlug((string) $validated['title']),
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'youtube_url' => $validated['type'] === 'youtube_video' ? ($validated['youtube_url'] ?? null) : null,
            'tags' => $this->normalizeTags($validated['tags'] ?? null),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];

        if ($validated['type'] === 'image') {
            if ($request->hasFile('image')) {
                if ($gallery->image_path && Storage::disk('public')->exists($gallery->image_path)) {
                    Storage::disk('public')->delete($gallery->image_path);
                }

                $payload['image_path'] = $request->file('image')->store('gallery', 'public');
            }
        } else {
            if ($gallery->image_path && Storage::disk('public')->exists($gallery->image_path)) {
                Storage::disk('public')->delete($gallery->image_path);
            }

            $payload['image_path'] = null;
        }

        $gallery->update($payload);

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery item updated successfully.');
    }

    public function destroy(GalleryItem $gallery)
    {
        if ($gallery->image_path && Storage::disk('public')->exists($gallery->image_path)) {
            Storage::disk('public')->delete($gallery->image_path);
        }

        $gallery->delete();

        return back()->with('success', 'Gallery item deleted successfully.');
    }

    private function normalizeTags(?string $tags): array
    {
        if (! $tags) {
            return [];
        }

        return collect(explode(',', $tags))
            ->map(fn ($tag) => Str::of($tag)->trim()->lower()->toString())
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (GalleryItem::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function isYouTubeUrl(string $url): bool
    {
        $lowercaseUrl = Str::lower($url);

        return Str::contains($lowercaseUrl, ['youtube.com', 'youtu.be']);
    }
}
