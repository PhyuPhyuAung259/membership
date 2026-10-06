<?php

use App\Livewire\Roles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('lets an admin view the roles page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('roles'))->assertOk();
});

it('blocks a staff member without manage-roles from the roles page', function () {
    $this->actingAs(User::factory()->staff()->create());

    $this->get(route('roles'))->assertForbidden();
});

it('creates a role with a chosen subset of permissions', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Roles::class)
        ->call('startAdd')
        ->set('name', 'Event Coordinator')
        ->set('permissions', ['manage-events', 'send-announcements'])
        ->call('save')
        ->assertHasNoErrors();

    $role = Role::where('name', 'Event Coordinator')->sole();

    expect($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['manage-events', 'send-announcements']);
});

it('rejects a permission key that is not in the fixed list', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Roles::class)
        ->call('startAdd')
        ->set('name', 'Bogus Role')
        ->set('permissions', ['not-a-real-permission'])
        ->call('save')
        ->assertHasErrors(['permissions.0']);
});

it('edits an existing role\'s permissions', function () {
    $this->actingAs(User::factory()->create());
    $role = Role::create(['name' => 'Bookkeeper', 'guard_name' => 'web']);
    $role->syncPermissions(['manage-events']);

    Livewire::test(Roles::class)
        ->call('startEdit', $role->id)
        ->set('permissions', ['delete-payments', 'manage-membership-status'])
        ->call('save')
        ->assertHasNoErrors();

    expect($role->fresh()->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['delete-payments', 'manage-membership-status']);
});

it('refuses to edit the protected Admin role', function () {
    $this->actingAs(User::factory()->create());
    $admin = Role::where('name', 'Admin')->sole();

    Livewire::test(Roles::class)
        ->call('startEdit', $admin->id)
        ->assertStatus(403);
});

it('refuses to delete the protected Admin role', function () {
    $this->actingAs(User::factory()->create());
    $admin = Role::where('name', 'Admin')->sole();

    Livewire::test(Roles::class)
        ->call('delete', $admin->id)
        ->assertStatus(403);
});

it('refuses to delete a role that staff are still assigned to', function () {
    $this->actingAs(User::factory()->create());
    User::factory()->staff()->create();
    $staffRole = Role::where('name', 'Staff')->sole();

    Livewire::test(Roles::class)->call('delete', $staffRole->id);

    expect(Role::find($staffRole->id))->not->toBeNull();
});

it('deletes a role nobody is assigned to', function () {
    $this->actingAs(User::factory()->create());
    $role = Role::create(['name' => 'Unused Role', 'guard_name' => 'web']);

    Livewire::test(Roles::class)->call('delete', $role->id);

    expect(Role::find($role->id))->toBeNull();
});

it('actually restricts access once a custom role is applied to a user', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Roles::class)
        ->call('startAdd')
        ->set('name', 'Event Coordinator')
        ->set('permissions', ['manage-events'])
        ->call('save');

    $coordinator = User::factory()->staff()->create();
    $coordinator->syncRoles(['Event Coordinator']);

    $this->actingAs($coordinator);

    $this->get(route('events'))->assertOk();
    $this->get(route('members'))->assertForbidden();
});
