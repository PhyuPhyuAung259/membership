<?php

use App\Livewire\MemberTypes;
use App\Models\Member;
use App\Models\MemberType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('adds a member type', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(MemberTypes::class)
        ->call('startEdit')
        ->set('form.name', 'Gold')
        ->set('form.monthly_fee', '150')
        ->set('form.sort_order', 1)
        ->call('save')
        ->assertHasNoErrors();

    $type = MemberType::where('name', 'Gold')->sole();
    expect((float) $type->monthly_fee)->toBe(150.0);
});

it('requires a name', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(MemberTypes::class)
        ->call('startEdit')
        ->set('form.monthly_fee', '50')
        ->call('save')
        ->assertHasErrors(['form.name' => 'required']);
});

it('edits an existing member type', function () {
    $this->actingAs(User::factory()->create());
    $type = MemberType::create(['name' => 'Silver', 'monthly_fee' => 80]);

    Livewire::test(MemberTypes::class)
        ->call('startEdit', $type->id)
        ->set('form.monthly_fee', '95')
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $type->fresh()->monthly_fee)->toBe(95.0);
});

it('refuses to delete a member type that members are on', function () {
    $this->actingAs(User::factory()->create());
    $type = MemberType::create(['name' => 'Platinum', 'monthly_fee' => 300]);

    Member::create([
        'company_name' => 'Acme Corp',
        'email' => 'acme@test.invalid',
        'join_date' => now()->toDateString(),
        'member_type_id' => $type->id,
    ]);

    Livewire::test(MemberTypes::class)->call('delete', $type->id);

    expect(MemberType::find($type->id))->not->toBeNull();
});

it('deletes a member type that is not in use', function () {
    $this->actingAs(User::factory()->create());
    $type = MemberType::create(['name' => 'Unused Tier', 'monthly_fee' => 10]);

    Livewire::test(MemberTypes::class)->call('delete', $type->id);

    expect(MemberType::find($type->id))->toBeNull();
});
