<?php

namespace App\Http\Controllers;

use App\Models\GalleryItem;
use Inertia\Inertia;
use Inertia\Response;

class GalleryItemController extends Controller
{
    public function index(): Response
    {
        $items = GalleryItem::query()
            ->active()
            ->ordered()
            ->paginate(18)
            ->withQueryString();

        return Inertia::render('Gallery/Index', [
            'items' => $items,
        ]);
    }

    public function show(GalleryItem $galleryItem): Response
    {
        abort_unless($galleryItem->is_active, 404);

        $related = GalleryItem::query()
            ->active()
            ->whereKeyNot($galleryItem->id)
            ->when(! empty($galleryItem->tags), function ($query) use ($galleryItem) {
                $query->where(function ($builder) use ($galleryItem) {
                    foreach ($galleryItem->tags as $tag) {
                        $builder->orWhereJsonContains('tags', $tag);
                    }
                });
            })
            ->ordered()
            ->limit(6)
            ->get();

        return Inertia::render('Gallery/Show', [
            'item' => $galleryItem,
            'relatedItems' => $related,
        ]);
    }
}
