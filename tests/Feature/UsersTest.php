<?php

use App\Livewire\Users;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('lets an admin view the staff accounts page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('users'))->assertOk();
});

it('blocks a staff member from the staff accounts page', function () {
    $this->actingAs(User::factory()->staff()->create());

    $this->get(route('users'))->assertForbidden();
});

it('blocks a staff member from the staff accounts component directly', function () {
    $this->actingAs(User::factory()->staff()->create());

    Livewire::test(Users::class)->assertStatus(403);
});

it('creates a staff account with a generated password that actually works', function () {
    $this->actingAs(User::factory()->create());

    $component = Livewire::test(Users::class)
        ->call('startAdd')
        ->set('form.name', 'New Staffer')
        ->set('form.email', 'newstaffer@test.invalid')
        ->set('form.role', 'Staff')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('formOpen', false);

    $user = User::where('email', 'newstaffer@test.invalid')->sole();
    expect($user->hasRole('Admin'))->toBeFalse()
        ->and($user->hasRole('Staff'))->toBeTrue();

    $password = $component->get('generatedPassword');
    expect($password)->not->toBeNull();

    Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', $password)
        ->call('login')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($user);
});

it('requires a unique email for a new staff account', function () {
    $this->actingAs(User::factory()->create());
    $existing = User::factory()->create(['email' => 'taken@test.invalid']);

    Livewire::test(Users::class)
        ->call('startAdd')
        ->set('form.name', 'Dupe')
        ->set('form.email', $existing->email)
        ->set('form.role', 'Staff')
        ->call('save')
        ->assertHasErrors(['form.email']);
});

it('lets an admin change another account\'s role', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);
    $staffer = User::factory()->staff()->create();

    Livewire::test(Users::class)->call('setRole', $staffer->id, 'Admin');

    expect($staffer->fresh()->hasRole('Admin'))->toBeTrue();
});

it('refuses to let an admin change their own role', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    Livewire::test(Users::class)->call('setRole', $admin->id, 'Staff');

    expect($admin->fresh()->hasRole('Admin'))->toBeTrue();
});

it('refuses to delete an admin\'s own account', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    Livewire::test(Users::class)->call('delete', $admin->id);

    expect(User::find($admin->id))->not->toBeNull();
});

it('deletes a staff account that is not the acting admin', function () {
    $this->actingAs(User::factory()->create());
    $staffer = User::factory()->staff()->create();

    Livewire::test(Users::class)->call('delete', $staffer->id);

    expect(User::find($staffer->id))->toBeNull();
});
