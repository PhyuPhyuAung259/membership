<?php

use App\Livewire\Portal\ForgotPassword;
use App\Livewire\Portal\Login;
use App\Livewire\Portal\ResetPassword;
use App\Models\EmailLog;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function portalMember(array $attrs = []): Member
{
    return Member::create(array_merge([
        'company_name' => 'Portal Co',
        'email' => 'portal' . uniqid() . '@test.invalid',
        'join_date' => now()->subYear()->toDateString(),
    ], $attrs));
}

it('guests are redirected to the portal login, not the staff one', function () {
    $this->get(route('portal.dashboard'))->assertRedirect(route('portal.login'));
});

it('logs a member in with the correct password', function () {
    $member = portalMember();
    $member->setPortalPassword('correct-password');

    Livewire::test(Login::class)
        ->set('email', $member->email)
        ->set('password', 'correct-password')
        ->call('login')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($member, 'member');
});

it('rejects the wrong password', function () {
    $member = portalMember();
    $member->setPortalPassword('correct-password');

    Livewire::test(Login::class)
        ->set('email', $member->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest('member');
});

it('refuses to log in a member who has never set a password', function () {
    $member = portalMember(); // no setPortalPassword() call

    Livewire::test(Login::class)
        ->set('email', $member->email)
        ->set('password', 'anything')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest('member');
});

it('does not let a staff session reach portal routes', function () {
    $this->actingAs(\App\Models\User::factory()->create());

    $this->get(route('portal.dashboard'))->assertRedirect(route('portal.login'));
});

it('does not let a member session reach staff routes', function () {
    $member = portalMember();
    $member->setPortalPassword('correct-password');
    $this->actingAs($member, 'member');

    $this->get(route('members'))->assertRedirect(route('login'));
});

it('sends a reset link and logs it, without revealing whether the email exists', function () {
    $member = portalMember();
    $member->setPortalPassword('old-password');

    $withAccount = Livewire::test(ForgotPassword::class)
        ->set('email', $member->email)
        ->call('sendLink');

    $withoutAccount = Livewire::test(ForgotPassword::class)
        ->set('email', 'nobody-here@test.invalid')
        ->call('sendLink');

    expect($withAccount->get('status'))->toContain($member->email)
        ->and($withoutAccount->get('status'))->toContain('nobody-here@test.invalid');

    expect(EmailLog::where('kind', 'portal_password_reset')->where('member_id', $member->id)->exists())->toBeTrue();
});

it('resets the password with a valid token and can then log in with it', function () {
    $member = portalMember();
    $member->setPortalPassword('old-password');

    $token = Password::broker('members')->createToken($member);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('email', $member->email)
        ->set('password', 'brand-new-password')
        ->set('password_confirmation', 'brand-new-password')
        ->call('resetPassword')
        ->assertHasNoErrors();

    // Proven directly against the guard rather than by driving a second
    // Livewire component in the same test — chaining two separate
    // Livewire::test() lifecycles in one test shares app state in ways
    // that don't reflect two real, separate page loads.
    expect(Auth::guard('member')->validate([
        'email' => $member->email,
        'password' => 'brand-new-password',
    ]))->toBeTrue();
});

it('rejects an invalid reset token', function () {
    $member = portalMember();

    Livewire::test(ResetPassword::class, ['token' => 'not-a-real-token'])
        ->set('email', $member->email)
        ->set('password', 'brand-new-password')
        ->set('password_confirmation', 'brand-new-password')
        ->call('resetPassword')
        ->assertHasErrors(['email']);

    expect($member->fresh()->hasPortalAccess())->toBeFalse();
});
