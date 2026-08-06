<?php

namespace Database\Seeders;

use App\Models\CourseCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CourseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'Programming & Development',
                'description' => 'Software development, web, mobile, and backend engineering.',
                'icon'        => 'code',
                'color'       => '#381998',
                'sort_order'  => 1,
            ],
            [
                'name'        => 'Data Science & AI',
                'description' => 'Machine learning, data analysis, and artificial intelligence.',
                'icon'        => 'bot',
                'color'       => '#42b6c5',
                'sort_order'  => 2,
            ],
            [
                'name'        => 'UI/UX & Design',
                'description' => 'Product design, user experience, and graphic design.',
                'icon'        => 'palette',
                'color'       => '#7c3aed',
                'sort_order'  => 3,
            ],
            [
                'name'        => 'Business & Management',
                'description' => 'Entrepreneurship, project management, and leadership skills.',
                'icon'        => 'bar-chart',
                'color'       => '#059669',
                'sort_order'  => 4,
            ],
            [
                'name'        => 'Cybersecurity',
                'description' => 'Network security, ethical hacking, and digital forensics.',
                'icon'        => 'shield',
                'color'       => '#dc2626',
                'sort_order'  => 5,
            ],
            [
                'name'        => 'Cloud & DevOps',
                'description' => 'Cloud infrastructure, CI/CD pipelines, and deployment.',
                'icon'        => 'cloud',
                'color'       => '#2563eb',
                'sort_order'  => 6,
            ],
            [
                'name'        => 'Digital Marketing',
                'description' => 'SEO, social media, content marketing, and analytics.',
                'icon'        => 'megaphone',
                'color'       => '#d97706',
                'sort_order'  => 7,
            ],
            [
                'name'        => 'Finance & Accounting',
                'description' => 'Financial literacy, accounting, and investment fundamentals.',
                'icon'        => 'wallet',
                'color'       => '#0891b2',
                'sort_order'  => 8,
            ],
        ];

        foreach ($categories as $data) {
            CourseCategory::firstOrCreate(
                ['slug' => Str::slug($data['name'])],
                array_merge($data, ['is_active' => true]),
            );
        }

        $this->command->info('Course categories seeded (' . count($categories) . ' categories).');
    }
}
