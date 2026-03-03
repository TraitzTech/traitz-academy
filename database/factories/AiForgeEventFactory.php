<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiForgeEvent>
 */
class AiForgeEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'AI Forge',
            'slug' => 'ai-forge',
            'tagline' => 'Master AI, Build the Future',
            'description' => fake()->paragraphs(3, true),
            'short_description' => fake()->sentence(15),
            'start_date' => '2026-04-14',
            'end_date' => '2026-06-20',
            'location' => 'Buea, Cameroon',
            'is_online' => false,
            'capacity' => 200,
            'is_active' => true,
            'registration_open' => true,
            'registration_fee' => 5000,
            'early_bird_fee' => 2000,
            'early_bird_deadline' => '2026-03-31',
            'currency' => 'XAF',
            'swag_store_active' => true,
            'benefits' => [
                ['title' => 'Gemini Pro Access', 'description' => '4 months free Gemini Pro from program start', 'icon' => 'sparkles'],
                ['title' => 'Expert Mentorship', 'description' => 'Learn from industry AI practitioners', 'icon' => 'users'],
                ['title' => 'Certification', 'description' => 'Receive an AI Forge completion certificate', 'icon' => 'award'],
                ['title' => 'Project Portfolio', 'description' => 'Build real-world AI projects', 'icon' => 'briefcase'],
            ],
            'schedule' => [
                ['week' => 'Week 1-2', 'title' => 'AI Fundamentals', 'description' => 'Understanding AI, ML basics, prompt engineering'],
                ['week' => 'Week 3-4', 'title' => 'AI for Business', 'description' => 'Using AI tools for entrepreneurship and management'],
                ['week' => 'Week 5-6', 'title' => 'Hands-on Projects', 'description' => 'Build AI-powered applications'],
                ['week' => 'Week 7-8', 'title' => 'AI Startup Lab', 'description' => 'Launch your AI business idea'],
            ],
            'faqs' => [
                ['question' => 'Is this program free?', 'answer' => 'No, There is a registration fee involved with the program!'],
                ['question' => 'Do I need prior AI experience?', 'answer' => 'No, we cover everything from basics to advanced.'],
                ['question' => 'Will there be certificates?', 'answer' => 'Yes, all participants who complete the program receive a certificate.'],
            ],
            'stats' => [
                'total_registered' => 0,
                'total_swags_sold' => 0,
            ],
        ];
    }
}
