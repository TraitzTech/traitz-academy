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
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@traitzacademy.com',
            'phone' => '+1234567890',
            'role' => User::ROLE_CTO,
            'password' => bcrypt('password'),
        ]);

        // Create regular test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+0987654321',
            'role' => 'user',
        ]);

        // // Create programs
        // $programs = Program::factory(15)->create();

        // // Create events
        // $events = Event::factory(8)->create();

        // // Create applications for each program
        // foreach ($programs as $program) {
        //     Application::factory(fake()->numberBetween(5, 20))->for($program)->create();
        // }

        // // Create event registrations for each event
        // foreach ($events as $event) {
        //     EventRegistration::factory(fake()->numberBetween(10, 50))->for($event)->create();
        // }
    }
}
