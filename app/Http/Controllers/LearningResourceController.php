<?php

namespace App\Http\Controllers;

use App\Models\LearningResource;
use Inertia\Inertia;
use Inertia\Response;

class LearningResourceController extends Controller
{
    public function index(): Response
    {
        $resources = LearningResource::query()
            ->active()
            ->ordered()
            ->paginate(18)
            ->withQueryString();

        return Inertia::render('Resources/Index', [
            'resources' => $resources,
        ]);
    }

    public function show(LearningResource $learningResource): Response
    {
        abort_unless($learningResource->is_active, 404);

        $related = LearningResource::query()
            ->active()
            ->whereKeyNot($learningResource->id)
            ->when(! empty($learningResource->tags), function ($query) use ($learningResource) {
                $query->where(function ($builder) use ($learningResource) {
                    foreach ($learningResource->tags as $tag) {
                        $builder->orWhereJsonContains('tags', $tag);
                    }
                });
            })
            ->ordered()
            ->limit(6)
            ->get();

        return Inertia::render('Resources/Show', [
            'resource' => $learningResource,
            'relatedResources' => $related,
        ]);
    }
}
