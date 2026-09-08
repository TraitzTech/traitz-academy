<?php

use App\Models\CommunityMember;
use App\Models\SiteSetting;
use App\Notifications\Tac\TacJoinConfirmation;
use App\Notifications\Tac\TacWelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('includes the WhatsApp community link in the join confirmation email by default', function () {
    $member = CommunityMember::factory()->create();

    $mail = (new TacJoinConfirmation($member))->toMail($member);

    expect(collect([...$mail->introLines, ...$mail->outroLines])->join(' '))
        ->toContain('chat.whatsapp.com/DhRne7d98y8K2aM0wu8Rlr');
});

it('includes the WhatsApp community link in the auto-join welcome email by default', function () {
    $member = CommunityMember::factory()->create();

    $mail = (new TacWelcomeNotification($member))->toMail($member);

    expect(collect([...$mail->introLines, ...$mail->outroLines])->join(' '))
        ->toContain('chat.whatsapp.com/DhRne7d98y8K2aM0wu8Rlr');
});

it('omits the WhatsApp link from both emails once the admin toggles it off', function () {
    SiteSetting::set('tac_join_whatsapp_enabled', '0');
    $member = CommunityMember::factory()->create();

    $joinLines = collect((new TacJoinConfirmation($member))->toMail($member)->introLines)->join(' ');
    $welcomeLines = collect((new TacWelcomeNotification($member))->toMail($member)->introLines)->join(' ');

    expect($joinLines)->not->toContain('chat.whatsapp.com')
        ->and($welcomeLines)->not->toContain('chat.whatsapp.com');
});

it('uses whatever link the admin sets instead of the default', function () {
    SiteSetting::set('tac_join_whatsapp_link', 'https://chat.whatsapp.com/CustomLinkHere');
    $member = CommunityMember::factory()->create();

    $mail = (new TacJoinConfirmation($member))->toMail($member);

    expect(collect([...$mail->introLines, ...$mail->outroLines])->join(' '))
        ->toContain('chat.whatsapp.com/CustomLinkHere')
        ->not->toContain('DhRne7d98y8K2aM0wu8Rlr');
});
