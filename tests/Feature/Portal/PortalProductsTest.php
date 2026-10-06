<?php

use App\Livewire\Portal\Products;
use App\Models\Member;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function portalProductsMember(array $attrs = []): Member
{
    return Member::create(array_merge([
        'company_name' => 'Portal Co',
        'email' => 'portal' . uniqid() . '@test.invalid',
        'join_date' => now()->subYear()->toDateString(),
    ], $attrs));
}

it('lets a member add their own product', function () {
    $member = portalProductsMember();
    $member->setPortalPassword('secret-pass');
    $this->actingAs($member, 'member');

    Livewire::test(Products::class)
        ->call('startAdd')
        ->set('form.product_name', 'Hand-rolled Cigars')
        ->set('form.description', 'Locally made.')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('member_id', $member->id)->sole();
    expect($product->product_name)->toBe('Hand-rolled Cigars');
});

it('refuses to edit another member\'s product', function () {
    $owner = portalProductsMember();
    $product = Product::create(['member_id' => $owner->id, 'product_name' => 'Not Yours']);

    $attacker = portalProductsMember();
    $attacker->setPortalPassword('secret-pass');
    $this->actingAs($attacker, 'member');

    Livewire::test(Products::class)->call('startEdit', $product->id)->assertStatus(404);
});

it('refuses to delete another member\'s product', function () {
    $owner = portalProductsMember();
    $product = Product::create(['member_id' => $owner->id, 'product_name' => 'Not Yours']);

    $attacker = portalProductsMember();
    $attacker->setPortalPassword('secret-pass');
    $this->actingAs($attacker, 'member');

    Livewire::test(Products::class)->call('delete', $product->id)->assertStatus(404);

    expect(Product::find($product->id))->not->toBeNull();
});

it('only lists the signed-in member\'s own products', function () {
    $member = portalProductsMember();
    $member->setPortalPassword('secret-pass');
    Product::create(['member_id' => $member->id, 'product_name' => 'Mine']);

    $other = portalProductsMember();
    Product::create(['member_id' => $other->id, 'product_name' => 'Theirs']);

    $this->actingAs($member, 'member');

    Livewire::test(Products::class)
        ->assertSee('Mine')
        ->assertDontSee('Theirs');
});
