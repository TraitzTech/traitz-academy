<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@traitzacademy.com'],
            [
                'name' => 'Admin User',
                'phone' => '+1234567890',
                'role' => User::ROLE_CTO,
                'password' => bcrypt('password'),
            ]
        );

        // Create regular test user
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'phone' => '+0987654321',
                'role' => 'user',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create tutor user
        User::firstOrCreate(
            ['email' => 'tutor@traitzacademy.com'],
            [
                'name' => 'Test Tutor',
                'phone' => '+1122334455',
                'role' => User::ROLE_TUTOR,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->call(CourseCategorySeeder::class);
    }

}
