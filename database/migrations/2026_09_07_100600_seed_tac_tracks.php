<?php

use App\Models\TacTrack;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * The eight tracks TAC organises around. Seeded as data (not hardcoded in
     * views) so admins can rename, reorder, retire or add to them without a
     * deploy — while still giving the platform something real on day one.
     */
    private const TRACKS = [
        [
            'name' => 'Web Development',
            'slug' => 'web-development',
            'tagline' => 'Build for the browser, ship for the world.',
            'description' => 'Front-end, back-end and full-stack engineering — from semantic HTML and modern JavaScript frameworks to APIs, databases and deployment.',
            'icon' => 'Code2',
            'accent_color' => '#42b6c5',
        ],
        [
            'name' => 'Mobile App Development',
            'slug' => 'mobile-app-development',
            'tagline' => 'Software people carry in their pocket.',
            'description' => 'Native and cross-platform mobile engineering: Android, iOS, Flutter and React Native, plus the app-store craft of shipping and maintaining a release.',
            'icon' => 'Smartphone',
            'accent_color' => '#381998',
        ],
        [
            'name' => 'UI/UX & Graphic Design',
            'slug' => 'ui-ux-graphic-design',
            'tagline' => 'Design that people understand without being told.',
            'description' => 'User research, wireframing, prototyping, interaction and visual design — plus the brand and graphic work that carries a product to its audience.',
            'icon' => 'Palette',
            'accent_color' => '#e0669d',
        ],
        [
            'name' => 'AI & Machine Learning',
            'slug' => 'ai-machine-learning',
            'tagline' => 'Teach machines to do useful work.',
            'description' => 'Applied machine learning, deep learning and generative AI — data preparation, model training, evaluation, and putting models into real products.',
            'icon' => 'BrainCircuit',
            'accent_color' => '#7c3aed',
        ],
        [
            'name' => 'Networking & Cybersecurity',
            'slug' => 'networking-cybersecurity',
            'tagline' => 'Keep systems connected, and keep them safe.',
            'description' => 'Network design and administration, systems hardening, penetration testing, incident response and the defensive practice of securing real infrastructure.',
            'icon' => 'ShieldCheck',
            'accent_color' => '#0ea5e9',
        ],
        [
            'name' => 'Digital Marketing',
            'slug' => 'digital-marketing',
            'tagline' => 'Get the right product in front of the right people.',
            'description' => 'Content and social strategy, SEO, paid acquisition, analytics and community growth — marketing as a measurable, technical discipline.',
            'icon' => 'Megaphone',
            'accent_color' => '#f59e0b',
        ],
        [
            'name' => 'Data Science',
            'slug' => 'data-science',
            'tagline' => 'Turn raw data into decisions.',
            'description' => 'Statistics, data wrangling, visualisation and analysis — the work of moving from a messy dataset to an insight somebody can act on.',
            'icon' => 'ChartNoAxesCombined',
            'accent_color' => '#10b981',
        ],
        [
            'name' => 'Arduino Programming for IoT',
            'slug' => 'arduino-iot',
            'tagline' => 'Where code meets the physical world.',
            'description' => 'Embedded programming, sensors, actuators and connected devices — building hardware projects that sense, respond and report.',
            'icon' => 'CircuitBoard',
            'accent_color' => '#ef4444',
        ],
    ];

    public function up(): void
    {
        foreach (self::TRACKS as $index => $track) {
            TacTrack::query()->updateOrCreate(
                ['slug' => $track['slug']],
                [
                    ...$track,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ],
            );
        }
    }

    public function down(): void
    {
        TacTrack::query()
            ->whereIn('slug', array_column(self::TRACKS, 'slug'))
            ->delete();
    }
};
