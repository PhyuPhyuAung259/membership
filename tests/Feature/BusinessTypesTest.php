<?php

use App\Livewire\BusinessTypes;
use App\Models\BusinessType;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('adds a business type', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BusinessTypes::class)
        ->set('name', 'Hospitality')
        ->call('add')
        ->assertHasNoErrors();

    expect(BusinessType::where('name', 'Hospitality')->exists())->toBeTrue();
});

it('renames a business type', function () {
    $this->actingAs(User::factory()->create());
    $type = BusinessType::create(['name' => 'Retail']);

    Livewire::test(BusinessTypes::class)
        ->call('startRename', $type->id)
        ->set('editingName', 'Retail & Wholesale')
        ->call('rename')
        ->assertHasNoErrors();

    expect($type->fresh()->name)->toBe('Retail & Wholesale');
});

it('refuses to delete a business type that members are on', function () {
    $this->actingAs(User::factory()->create());
    $type = BusinessType::create(['name' => 'Manufacturing']);

    Member::create([
        'company_name' => 'Acme Factory',
        'email' => 'acme@test.invalid',
        'join_date' => now()->toDateString(),
        'business_type_id' => $type->id,
    ]);

    Livewire::test(BusinessTypes::class)->call('delete', $type->id);

    expect(BusinessType::find($type->id))->not->toBeNull();
});

it('deletes a business type that is not in use', function () {
    $this->actingAs(User::factory()->create());
    $type = BusinessType::create(['name' => 'Unused']);

    Livewire::test(BusinessTypes::class)->call('delete', $type->id);

    expect(BusinessType::find($type->id))->toBeNull();
});
