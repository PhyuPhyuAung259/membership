<?php

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
| Member::billingState() computes standing in PHP; the query scopes compute
| the same thing in SQL so lists can be filtered and paged without loading
| every member. That duplication is a real risk, so this test asserts the two
| always agree. If you change one, change the other and run this.
*/

function memberWith(array $attrs): Member
{
    $member = Member::create(array_merge([
        'company_name' => 'Test Member',
        'email' => 'm' . uniqid() . '@test.invalid',
        'monthly_fee' => 25,
        'join_date' => now()->subYear()->toDateString(),
    ], $attrs));

    if (array_key_exists('paid_through', $attrs)) {
        // paid_through is trigger-maintained, so set it via a payment.
        $member->payments()->create([
            'amount' => 25,
            'paid_on' => now()->toDateString(),
            'period_start' => now()->subYear()->toDateString(),
            'period_end' => $attrs['paid_through'],
        ]);
        $member->refresh();
    }

    return $member;
}

it('agrees between the PHP accessor and the SQL scopes', function () {
    memberWith(['paid_through' => now()->addDays(20)->toDateString()]);   // current
    memberWith(['paid_through' => now()->addDays(3)->toDateString()]);    // current, due soon
    memberWith(['paid_through' => now()->subDay()->toDateString()]);      // overdue
    memberWith(['paid_through' => now()->subDays(40)->toDateString()]);   // overdue
    memberWith([]);                                                       // never paid
    memberWith(['status' => 'cancelled']);                                // cancelled

    $currentByScope = Member::current()->pluck('id')->sort()->values();
    $currentByAccessor = Member::all()
        ->filter(fn (Member $m) => $m->billingState() === 'current')
        ->pluck('id')->sort()->values();

    expect($currentByScope->all())->toBe($currentByAccessor->all());

    $overdueByScope = Member::overdue()->pluck('id')->sort()->values();
    $overdueByAccessor = Member::all()
        ->filter(fn (Member $m) => in_array($m->billingState(), ['overdue', 'never_paid'], true))
        ->pluck('id')->sort()->values();

    expect($overdueByScope->all())->toBe($overdueByAccessor->all());
});

it('excludes cancelled members from both current and overdue', function () {
    $cancelled = memberWith(['status' => 'cancelled']);

    expect(Member::current()->pluck('id'))->not->toContain($cancelled->id)
        ->and(Member::overdue()->pluck('id'))->not->toContain($cancelled->id)
        ->and($cancelled->billingState())->toBe('cancelled');
});

it('finds members whose coverage ends within the window', function () {
    $soon = memberWith(['paid_through' => now()->addDays(3)->toDateString()]);
    $later = memberWith(['paid_through' => now()->addDays(40)->toDateString()]);

    $ids = Member::dueWithin(7)->pluck('id');

    expect($ids)->toContain($soon->id)->not->toContain($later->id);
});
