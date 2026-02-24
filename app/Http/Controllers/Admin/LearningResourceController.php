<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LearningResourceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = LearningResource::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
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

        $resources = $query->ordered()->paginate(12)->withQueryString();

        return Inertia::render('Admin/Resources/Index', [
            'resources' => $resources,
            'filters' => $request->only(['search', 'type', 'status', 'tag']),
            'types' => [
                'document' => 'Document',
                'youtube_video' => 'YouTube Video',
                'writing' => 'Writing',
                'external_link' => 'External Link',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Resources/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:document,youtube_video,writing,external_link'],
            'description' => ['nullable', 'string', 'max:3000'],
            'document' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,txt', 'max:10240'],
            'youtube_url' => ['nullable', 'url', 'max:500'],
            'external_url' => ['nullable', 'url', 'max:500'],
            'content' => ['nullable', 'string'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        if ($validated['type'] === 'document' && ! $request->hasFile('document')) {
            return back()->withErrors(['document' => 'A document file is required for document resources.'])->withInput();
        }

        if ($validated['type'] === 'youtube_video' && empty($validated['youtube_url'])) {
            return back()->withErrors(['youtube_url' => 'A YouTube link is required for video resources.'])->withInput();
        }

        if ($validated['type'] === 'youtube_video' && ! $this->isYouTubeUrl((string) $validated['youtube_url'])) {
            return back()->withErrors(['youtube_url' => 'Please provide a valid YouTube URL.'])->withInput();
        }

        if ($validated['type'] === 'external_link' && empty($validated['external_url'])) {
            return back()->withErrors(['external_url' => 'An external URL is required for link resources.'])->withInput();
        }

        if ($validated['type'] === 'writing' && empty($validated['content'])) {
            return back()->withErrors(['content' => 'Writing content is required for writing resources.'])->withInput();
        }

        $slug = $this->generateUniqueSlug((string) $validated['title']);

        $payload = [
            'title' => $validated['title'],
            'slug' => $slug,
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'youtube_url' => $validated['type'] === 'youtube_video' ? ($validated['youtube_url'] ?? null) : null,
            'external_url' => $validated['type'] === 'external_link' ? ($validated['external_url'] ?? null) : null,
            'content' => $validated['type'] === 'writing' ? ($validated['content'] ?? null) : null,
            'tags' => $this->normalizeTags($validated['tags'] ?? null),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'published_at' => now(),
        ];

        if ($validated['type'] === 'document' && $request->hasFile('document')) {
            $payload['document_path'] = $request->file('document')->store('resources', 'public');
        }

        LearningResource::create($payload);

        return redirect()->route('admin.learning-resources.index')->with('success', 'Learning resource created successfully.');
    }

    public function edit(LearningResource $learningResource): Response
    {
        return Inertia::render('Admin/Resources/Edit', [
            'resource' => $learningResource,
            'tagsText' => implode(', ', $learningResource->tags ?? []),
        ]);
    }

    public function update(Request $request, LearningResource $learningResource)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:document,youtube_video,writing,external_link'],
            'description' => ['nullable', 'string', 'max:3000'],
            'document' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,txt', 'max:10240'],
            'youtube_url' => ['nullable', 'url', 'max:500'],
            'external_url' => ['nullable', 'url', 'max:500'],
            'content' => ['nullable', 'string'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        if ($validated['type'] === 'document' && ! $request->hasFile('document') && empty($learningResource->document_path)) {
            return back()->withErrors(['document' => 'A document file is required for document resources.'])->withInput();
        }

        if ($validated['type'] === 'youtube_video' && empty($validated['youtube_url'])) {
            return back()->withErrors(['youtube_url' => 'A YouTube link is required for video resources.'])->withInput();
        }

        if ($validated['type'] === 'youtube_video' && ! $this->isYouTubeUrl((string) $validated['youtube_url'])) {
            return back()->withErrors(['youtube_url' => 'Please provide a valid YouTube URL.'])->withInput();
        }

        if ($validated['type'] === 'external_link' && empty($validated['external_url'])) {
            return back()->withErrors(['external_url' => 'An external URL is required for link resources.'])->withInput();
        }

        if ($validated['type'] === 'writing' && empty($validated['content'])) {
            return back()->withErrors(['content' => 'Writing content is required for writing resources.'])->withInput();
        }

        $payload = [
            'title' => $validated['title'],
            'slug' => $validated['title'] === $learningResource->title ? $learningResource->slug : $this->generateUniqueSlug((string) $validated['title']),
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'youtube_url' => $validated['type'] === 'youtube_video' ? ($validated['youtube_url'] ?? null) : null,
            'external_url' => $validated['type'] === 'external_link' ? ($validated['external_url'] ?? null) : null,
            'content' => $validated['type'] === 'writing' ? ($validated['content'] ?? null) : null,
            'tags' => $this->normalizeTags($validated['tags'] ?? null),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];

        if ($validated['type'] === 'document') {
            if ($request->hasFile('document')) {
                if ($learningResource->document_path && Storage::disk('public')->exists($learningResource->document_path)) {
                    Storage::disk('public')->delete($learningResource->document_path);
                }

                $payload['document_path'] = $request->file('document')->store('resources', 'public');
            }
        } else {
            if ($learningResource->document_path && Storage::disk('public')->exists($learningResource->document_path)) {
                Storage::disk('public')->delete($learningResource->document_path);
            }

            $payload['document_path'] = null;
        }

        $learningResource->update($payload);

        return redirect()->route('admin.learning-resources.index')->with('success', 'Learning resource updated successfully.');
    }

    public function destroy(LearningResource $learningResource)
    {
        if ($learningResource->document_path && Storage::disk('public')->exists($learningResource->document_path)) {
            Storage::disk('public')->delete($learningResource->document_path);
        }

        $learningResource->delete();

        return back()->with('success', 'Learning resource deleted successfully.');
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

        while (LearningResource::where('slug', $slug)->exists()) {
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
