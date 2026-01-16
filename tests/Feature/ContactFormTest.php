<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

describe('Contact Form', function () {
    beforeEach(function () {
        // Ensure contact email setting exists
        SiteSetting::updateOrCreate(
            ['key' => 'contact_email'],
            [
                'value' => 'admin@traitzacademy.com',
                'type' => 'email',
                'group' => 'contact',
                'label' => 'Contact Email',
            ]
        );
    });

    test('contact page loads successfully', function () {
        $response = $this->get('/contact');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Contact')
        );
    });

    test('can submit contact form with valid data', function () {
        Notification::fake();

        $response = $this->post('/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Inquiry about Programs',
            'message' => 'I am interested in learning more about your programs.',
        ]);

        $response->assertSessionHas('success');
    });

    test('contact form validation works', function () {
        $response = $this->post('/contact', [
            'name' => '',
            'email' => 'invalid-email',
            'subject' => '',
            'message' => 'short',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
    });

    test('contact form requires all fields', function () {
        $response = $this->post('/contact', []);

        $response->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
    });

    test('message field has minimum length requirement', function () {
        $response = $this->post('/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Subject',
            'message' => 'short',
        ]);

        $response->assertSessionHasErrors('message');
    });

    test('site settings are shared with contact page', function () {
        $response = $this->get('/contact');

        $response->assertInertia(fn ($page) => $page
            ->has('siteSettings')
            ->has('siteSettings.contact_email')
            ->has('siteSettings.contact_phone')
            ->has('siteSettings.social_facebook')
        );
    });
});
