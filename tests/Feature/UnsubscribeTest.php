<?php

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

function signedLinks(Member $member): array
{
    $show = URL::temporarySignedRoute('unsubscribe.show', now()->addYear(), ['member' => $member->id]);

    return ['show' => $show, 'post' => str_replace('/unsubscribe/', '/unsubscribe/', $show)];
}

it('does not unsubscribe anyone on a GET', function () {
    // Mail clients and security scanners prefetch links in email. A GET that
    // changed the record would unsubscribe members who never clicked.
    $m = Member::create([
        'company_name' => 'Quiet', 'email' => 'quiet@test.invalid',
        'monthly_fee' => 25, 'join_date' => now()->toDateString(),
    ]);

    $this->get(signedLinks($m)['show'])->assertOk();

    expect($m->refresh()->unsubscribed_at)->toBeNull();
});

it('unsubscribes on the confirming POST', function () {
    $m = Member::create([
        'company_name' => 'Quiet', 'email' => 'quiet2@test.invalid',
        'monthly_fee' => 25, 'join_date' => now()->toDateString(),
    ]);

    $link = signedLinks($m)['post'];
    $this->post($link)->assertOk();

    $m->refresh();
    expect($m->unsubscribed_at)->not->toBeNull()
        ->and($m->marketing_opt_in)->toBeFalse();
});

it('rejects an unsigned or tampered link', function () {
    $m = Member::create([
        'company_name' => 'Quiet', 'email' => 'quiet3@test.invalid',
        'monthly_fee' => 25, 'join_date' => now()->toDateString(),
    ]);

    $this->get("/unsubscribe/{$m->id}")->assertForbidden();
    $this->get(signedLinks($m)['show'] . 'tampered')->assertForbidden();
});
