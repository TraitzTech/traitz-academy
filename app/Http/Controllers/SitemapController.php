<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Event;
use App\Models\GalleryItem;
use App\Models\LearningResource;
use App\Models\Program;
use App\Models\TacActivity;
use App\Models\TacTrack;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = collect();

        $static = [
            ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => '/about', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => '/programs', 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => '/events', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => '/online-courses', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => '/gallery', 'priority' => '0.5', 'changefreq' => 'weekly'],
            ['loc' => '/resources', 'priority' => '0.6', 'changefreq' => 'weekly'],
            ['loc' => '/success-stories', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => '/contact', 'priority' => '0.5', 'changefreq' => 'yearly'],
            ['loc' => '/community', 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => '/community/about', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => '/community/tracks', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => '/community/team', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => '/community/activities', 'priority' => '0.8', 'changefreq' => 'daily'],
            ['loc' => '/community/partners', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => '/community/join', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => '/community/get-involved', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ];

        foreach ($static as $entry) {
            $urls->push([
                'loc' => url($entry['loc']),
                'lastmod' => null,
                'changefreq' => $entry['changefreq'],
                'priority' => $entry['priority'],
            ]);
        }

        Program::query()->where('is_active', true)->get(['slug', 'updated_at'])->each(function ($program) use ($urls) {
            $urls->push([
                'loc' => url("/programs/{$program->slug}"),
                'lastmod' => $program->updated_at,
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ]);
        });

        Event::query()->where('is_active', true)->get(['slug', 'updated_at'])->each(function ($event) use ($urls) {
            $urls->push([
                'loc' => url("/events/{$event->slug}"),
                'lastmod' => $event->updated_at,
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ]);
        });

        Course::query()->where('status', 'published')->get(['id', 'updated_at'])->each(function ($course) use ($urls) {
            $urls->push([
                'loc' => url("/online-courses/{$course->id}"),
                'lastmod' => $course->updated_at,
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ]);
        });

        GalleryItem::query()->active()->get(['slug', 'updated_at'])->each(function ($item) use ($urls) {
            $urls->push([
                'loc' => url("/gallery/{$item->slug}"),
                'lastmod' => $item->updated_at,
                'changefreq' => 'monthly',
                'priority' => '0.4',
            ]);
        });

        LearningResource::query()->get(['slug', 'updated_at'])->each(function ($resource) use ($urls) {
            $urls->push([
                'loc' => url("/resources/{$resource->slug}"),
                'lastmod' => $resource->updated_at,
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ]);
        });

        TacTrack::query()->active()->get(['slug', 'updated_at'])->each(function ($track) use ($urls) {
            $urls->push([
                'loc' => url("/community/tracks/{$track->slug}"),
                'lastmod' => $track->updated_at,
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ]);
        });

        TacActivity::query()->published()->get(['slug', 'updated_at'])->each(function ($activity) use ($urls) {
            $urls->push([
                'loc' => url("/community/activities/{$activity->slug}"),
                'lastmod' => $activity->updated_at,
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ]);
        });

        $xml = view('sitemap', ['urls' => $urls]);

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }
}
