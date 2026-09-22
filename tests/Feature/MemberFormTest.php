<?php

use App\Livewire\MemberCreate;
use App\Livewire\Members;
use App\Models\Member;
use App\Models\MemberType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('adds a member from its own page and its public profile is reachable', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(MemberCreate::class)
        ->set('company_name', 'Riverside Bakery')
        ->set('email', 'riverside@test.invalid')
        ->set('phone', '0812345678')
        ->set('join_date', now()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    $member = Member::where('email', 'riverside@test.invalid')->sole();

    expect($member->company_name)->toBe('Riverside Bakery')
        ->and($member->status)->toBe('active');

    $this->get(route('directory.show', $member))
        ->assertOk()
        ->assertSee('Riverside Bakery');
});

it('requires a company name', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(MemberCreate::class)
        ->set('email', 'noname@test.invalid')
        ->set('join_date', now()->toDateString())
        ->call('save')
        ->assertHasErrors(['company_name' => 'required']);
});

it('leaves the fee override null when the field is left blank, falling back to the tier', function () {
    $this->actingAs(User::factory()->create());

    $tier = MemberType::create(['name' => 'Gold', 'monthly_fee' => 120]);

    Livewire::test(MemberCreate::class)
        ->set('company_name', 'Blank Fee Co')
        ->set('email', 'blankfee@test.invalid')
        ->set('member_type_id', $tier->id)
        ->set('join_date', now()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    $member = Member::where('email', 'blankfee@test.invalid')->sole();

    expect($member->monthly_fee)->toBeNull()
        ->and($member->effectiveMonthlyFee())->toBe(120.0);
});

it('accepts an about-the-company blurb within the 100-200 word range', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(MemberCreate::class)
        ->set('company_name', 'Wordy Co')
        ->set('email', 'wordy@test.invalid')
        ->set('join_date', now()->toDateString())
        ->set('address', '123 Main St, Yangon')
        ->set('about', str_repeat('word ', 150))
        ->call('save')
        ->assertHasNoErrors();

    $member = Member::where('email', 'wordy@test.invalid')->sole();

    expect($member->address)->toBe('123 Main St, Yangon')
        ->and(str_word_count($member->about))->toBe(150);
});

it('rejects an about-the-company blurb outside the 100-200 word range', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(MemberCreate::class)
        ->set('company_name', 'Too Short Co')
        ->set('email', 'tooshort@test.invalid')
        ->set('join_date', now()->toDateString())
        ->set('about', str_repeat('word ', 10))
        ->call('save')
        ->assertHasErrors(['about']);
});

it('leaves about optional when left blank', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(MemberCreate::class)
        ->set('company_name', 'No About Co')
        ->set('email', 'noabout@test.invalid')
        ->set('join_date', now()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    expect(Member::where('email', 'noabout@test.invalid')->sole()->about)->toBeNull();
});

it('lands a public self-registration as pending, not a live member', function () {
    // No actingAs — this is the unauthenticated, publicly shared form.
    Livewire::test(MemberCreate::class)
        ->set('company_name', 'Self Signup Co')
        ->set('email', 'selfsignup@test.invalid')
        ->set('join_date', now()->toDateString())
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $member = Member::where('email', 'selfsignup@test.invalid')->sole();

    expect($member->status)->toBe('pending')
        ->and($member->billingState())->toBe('pending');

    // Not vetted yet, so no public profile until staff activates it.
    $this->get(route('directory.show', $member))->assertNotFound();
});

it('activates a pending member from the list row', function () {
    $this->actingAs(User::factory()->create());

    $member = Member::create([
        'company_name' => 'Self Signup Co',
        'email' => 'activateme@test.invalid',
        'join_date' => now()->toDateString(),
        'status' => 'pending',
    ]);

    Livewire::test(Members::class)->call('activate', $member->id);

    expect($member->fresh()->status)->toBe('active');
});

it('refuses to record a payment against a pending member', function () {
    $this->actingAs(User::factory()->create());

    $member = Member::create([
        'company_name' => 'Self Signup Co',
        'email' => 'nopay@test.invalid',
        'join_date' => now()->toDateString(),
        'status' => 'pending',
    ]);

    Livewire::test(Members::class)
        ->call('startPayment', $member->id)
        ->assertStatus(403);
});

it('edits an existing member and keeps the same public profile url', function () {
    $this->actingAs(User::factory()->create());

    $member = Member::create([
        'company_name' => 'Old Name Co',
        'email' => 'oldname@test.invalid',
        'join_date' => now()->toDateString(),
    ]);

    Livewire::test(Members::class)
        ->call('startEdit', $member->id)
        ->set('form.company_name', 'New Name Co')
        ->call('saveMember')
        ->assertHasNoErrors();

    $this->get(route('directory.show', $member->fresh()))
        ->assertOk()
        ->assertSee('New Name Co');
});
