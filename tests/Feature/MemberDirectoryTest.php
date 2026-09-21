<?php

use App\Models\Member;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function directoryMember(array $attrs = []): Member
{
    return Member::create(array_merge([
        'company_name' => 'Acme Trading',
        'email' => 'acme' . uniqid() . '@test.invalid',
        'monthly_fee' => 60,
        'notes' => 'Chases invoices slowly.',
        'join_date' => now()->subYear()->toDateString(),
    ], $attrs));
}

it('shows the public profile for an active member', function () {
    $member = directoryMember();

    $this->get(route('directory.show', $member))
        ->assertOk()
        ->assertSee('Acme Trading');
});

it('never leaks billing details to the public page', function () {
    $member = directoryMember();

    $this->get(route('directory.show', $member))
        ->assertOk()
        ->assertDontSee('60.00')
        ->assertDontSee('Chases invoices slowly');
});

it('lists a member\'s products', function () {
    $member = directoryMember();
    Product::create([
        'member_id' => $member->id,
        'product_name' => 'Handwoven baskets',
        'description' => 'Made locally.',
        'file_path' => null,
        'file_kind' => null,
    ]);

    $this->get(route('directory.show', $member))
        ->assertOk()
        ->assertSee('Handwoven baskets')
        ->assertSee('Made locally.');
});

it('404s for a cancelled membership', function () {
    $member = directoryMember(['status' => 'cancelled']);

    $this->get(route('directory.show', $member))->assertNotFound();
});
