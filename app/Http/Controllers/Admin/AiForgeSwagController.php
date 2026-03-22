<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiForgeEvent;
use App\Models\AiForgeSwag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AiForgeSwagController extends Controller
{
    public function index(Request $request): Response
    {
        $event = AiForgeEvent::query()->first();

        $query = AiForgeSwag::query();

        if ($event) {
            $query->where('ai_forge_event_id', $event->id);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $swags = $query->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/AiForge/Swags/Index', [
            'event' => $event ? ['id' => $event->id, 'title' => $event->title] : null,
            'swags' => $swags,
            'filters' => $request->only(['search', 'category']),
            'categories' => $this->swagCategories(),
        ]);
    }

    public function create(): Response
    {
        $event = AiForgeEvent::query()->first();

        return Inertia::render('Admin/AiForge/Swags/Create', [
            'event' => $event ? ['id' => $event->id, 'title' => $event->title] : null,
            'categories' => $this->swagCategories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateSwag($request);
        $event = AiForgeEvent::query()->firstOrFail();

        $validated['ai_forge_event_id'] = $event->id;
        $validated['slug'] = Str::slug($validated['name'].'-'.Str::random(4));

        if ($request->hasFile('image_file')) {
            $validated['image'] = $request->file('image_file')->store('ai-forge/swags', 'public');
        }

        $this->handleGalleryImages($request, $validated);
        $this->handleVariations($request, $validated);
        $this->cleanVariationImageKeys($request, $validated);

        unset($validated['image_file'], $validated['gallery_files']);

        AiForgeSwag::create($validated);

        return redirect()->route('admin.ai-forge.swags.index')->with('success', 'Swag item created successfully.');
    }

    public function edit(AiForgeSwag $swag): Response
    {
        $event = AiForgeEvent::query()->first();

        return Inertia::render('Admin/AiForge/Swags/Edit', [
            'event' => $event ? ['id' => $event->id, 'title' => $event->title] : null,
            'swag' => $swag,
            'categories' => $this->swagCategories(),
        ]);
    }

    public function update(Request $request, AiForgeSwag $swag): RedirectResponse
    {
        $validated = $this->validateSwag($request);
        $validated['slug'] = Str::slug($validated['name'].'-'.Str::random(4));

        if ($request->hasFile('image_file')) {
            if ($swag->image && Storage::disk('public')->exists($swag->image)) {
                Storage::disk('public')->delete($swag->image);
            }
            $validated['image'] = $request->file('image_file')->store('ai-forge/swags', 'public');
        }

        $this->handleGalleryImages($request, $validated, $swag);
        $this->handleVariations($request, $validated);
        $this->cleanVariationImageKeys($request, $validated);

        unset($validated['image_file'], $validated['gallery_files']);

        $swag->update($validated);

        return redirect()->route('admin.ai-forge.swags.index')->with('success', 'Swag item updated successfully.');
    }

    public function destroy(AiForgeSwag $swag): RedirectResponse
    {
        if ($swag->image && Storage::disk('public')->exists($swag->image)) {
            Storage::disk('public')->delete($swag->image);
        }

        if ($swag->gallery_images) {
            foreach ($swag->gallery_images as $img) {
                if (Storage::disk('public')->exists($img)) {
                    Storage::disk('public')->delete($img);
                }
            }
        }

        if ($swag->variations) {
            foreach ($swag->variations as $type) {
                foreach ($type['options'] ?? [] as $option) {
                    if (is_array($option) && ! empty($option['image']) && Storage::disk('public')->exists($option['image'])) {
                        Storage::disk('public')->delete($option['image']);
                    }
                }
            }
        }

        $swag->delete();

        return redirect()->route('admin.ai-forge.swags.index')->with('success', 'Swag item deleted.');
    }

    public function toggleActive(AiForgeSwag $swag): RedirectResponse
    {
        $swag->update(['is_active' => ! $swag->is_active]);

        return back()->with('success', 'Swag status updated.');
    }

    public function toggleFeatured(AiForgeSwag $swag): RedirectResponse
    {
        $swag->update(['is_featured' => ! $swag->is_featured]);

        return back()->with('success', 'Featured status updated.');
    }

    /**
     * @return array<string, string>
     */
    private function swagCategories(): array
    {
        return [
            't-shirt' => 'T-Shirt',
            'polo' => 'Polo Shirt',
            'hoodie' => 'Hoodie',
            'cap' => 'Cap',
            'water-bottle' => 'Water Bottle',
            'sticker-pack' => 'Sticker Pack',
            'tote-bag' => 'Tote Bag',
            'notebook' => 'Notebook',
            'other' => 'Other',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSwag(Request $request): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:t-shirt,polo,hoodie,cap,water-bottle,sticker-pack,tote-bag,notebook,other'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
            'gallery_files' => ['nullable', 'array'],
            'gallery_files.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
            'variations' => ['nullable', 'string'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ];

        foreach ($request->allFiles() as $key => $file) {
            if (preg_match('/^variation_images_\d+_\d+$/', $key)) {
                $rules[$key] = ['image', 'mimes:jpeg,png,jpg,webp', 'max:3072'];
            }
        }

        return $request->validate($rules);
    }

    private function handleGalleryImages(Request $request, array &$validated, ?AiForgeSwag $swag = null): void
    {
        if ($request->hasFile('gallery_files')) {
            $galleryImages = $swag?->gallery_images ?? [];
            foreach ($request->file('gallery_files') as $file) {
                $galleryImages[] = $file->store('ai-forge/swags/gallery', 'public');
            }
            $validated['gallery_images'] = $galleryImages;
        }
    }

    private function handleVariations(Request $request, array &$validated): void
    {
        if (isset($validated['variations']) && is_string($validated['variations'])) {
            $decoded = json_decode($validated['variations'], true);

            if (is_array($decoded)) {
                foreach ($decoded as $i => &$type) {
                    if (! isset($type['options']) || ! is_array($type['options'])) {
                        continue;
                    }

                    foreach ($type['options'] as $j => &$option) {
                        if (is_string($option)) {
                            $option = ['name' => $option, 'image' => null];
                        }

                        $fileKey = "variation_images_{$i}_{$j}";
                        if ($request->hasFile($fileKey)) {
                            if (! empty($option['image']) && Storage::disk('public')->exists($option['image'])) {
                                Storage::disk('public')->delete($option['image']);
                            }

                            $option['image'] = $request->file($fileKey)->store('ai-forge/swags/variations', 'public');
                        }
                    }

                    unset($option);
                }

                unset($type);
                $validated['variations'] = $decoded;
            } else {
                $validated['variations'] = null;
            }
        }
    }

    /**
     * Remove variation image file keys from validated data so they don't cause mass-assignment issues.
     */
    private function cleanVariationImageKeys(Request $request, array &$validated): void
    {
        foreach (array_keys($validated) as $key) {
            if (preg_match('/^variation_images_\d+_\d+$/', $key)) {
                unset($validated[$key]);
            }
        }
    }
}
