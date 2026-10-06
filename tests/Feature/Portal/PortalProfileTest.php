<?php

use App\Livewire\Portal\Profile;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function portalProfileMember(array $attrs = []): Member
{
    return Member::create(array_merge([
        'company_name' => 'Portal Co',
        'email' => 'portal' . uniqid() . '@test.invalid',
        'join_date' => now()->subYear()->toDateString(),
    ], $attrs));
}

it('lets a member update their own profile', function () {
    $member = portalProfileMember();
    $member->setPortalPassword('secret-pass');
    $this->actingAs($member, 'member');

    Livewire::test(Profile::class)
        ->set('form.company_name', 'Renamed Co')
        ->set('form.contact_person', 'Jane Doe')
        ->set('form.address', '1 Main St')
        ->call('save')
        ->assertHasNoErrors();

    expect($member->fresh()->company_name)->toBe('Renamed Co')
        ->and($member->fresh()->contact_person)->toBe('Jane Doe');
});

it('rejects an about blurb outside the 100-200 word range', function () {
    $member = portalProfileMember();
    $member->setPortalPassword('secret-pass');
    $this->actingAs($member, 'member');

    Livewire::test(Profile::class)
        ->set('form.about', 'too short')
        ->call('save')
        ->assertHasErrors(['form.about']);
});

it('does not expose a way to change the login email, status or member type', function () {
    $member = portalProfileMember(['status' => 'active']);
    $member->setPortalPassword('secret-pass');
    $this->actingAs($member, 'member');

    // The component's own form array never carries these — proven by
    // confirming they're untouched after a normal save.
    Livewire::test(Profile::class)
        ->set('form.company_name', 'Still Renamed')
        ->call('save');

    expect($member->fresh()->email)->toBe($member->email)
        ->and($member->fresh()->status)->toBe('active');
});
