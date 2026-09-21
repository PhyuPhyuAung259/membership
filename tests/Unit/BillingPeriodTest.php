<?php

use App\Services\BillingPeriod;

it('proves native PHP month addition overflows', function () {
    // This is the bug BillingPeriod::addMonths() exists to avoid. If this
    // test ever fails, PHP changed and the clamping can be revisited.
    expect((new DateTimeImmutable('2026-01-31'))->modify('+1 month')->format('Y-m-d'))
        ->toBe('2026-03-03');
});

it('clamps month addition to the end of the target month', function (string $from, int $months, string $expected) {
    expect(BillingPeriod::addMonths(new DateTimeImmutable($from), $months)->format('Y-m-d'))
        ->toBe($expected);
})->with([
    ['2026-01-31', 1, '2026-02-28'],
    ['2028-01-31', 1, '2028-02-29'],   // leap year
    ['2026-03-31', 1, '2026-04-30'],
    ['2026-01-31', 12, '2027-01-31'],
    ['2026-12-15', 1, '2027-01-15'],   // year rollover
]);

it('starts a first payment at the join date', function () {
    expect(BillingPeriod::next(null, '2026-01-01', 1))
        ->toMatchArray(['period_start' => '2026-01-01', 'period_end' => '2026-01-31']);
});

it('keeps coverage continuous so a late payer loses no time', function () {
    // A member who pays three weeks late is credited from the day their cover
    // lapsed, not from today. They do not lose the time they were chased for,
    // and they do not quietly get a free stretch either.
    expect(BillingPeriod::next('2026-06-30', '2026-01-01', 1)['period_start'])->toBe('2026-07-01');
});

it('ends a February period on the right day', function () {
    expect(BillingPeriod::next('2026-01-31', '2026-01-01', 1)['period_end'])->toBe('2026-02-28');
    expect(BillingPeriod::next('2028-01-31', '2026-01-01', 1)['period_end'])->toBe('2028-02-29');
});

it('spans multiple months in a single period', function () {
    expect(BillingPeriod::next(null, '2026-01-01', 3)['period_end'])->toBe('2026-03-31');
    expect(BillingPeriod::next(null, '2026-01-01', 12)['period_end'])->toBe('2026-12-31');
});

it('coerces a nonsense month count up to one', function () {
    expect(BillingPeriod::next(null, '2026-01-01', 0)['months'])->toBe(1);
});

it('works out the due date from coverage or the join date', function () {
    expect(BillingPeriod::dueOn('2026-09-20', '2026-01-01'))->toBe('2026-09-21')
        ->and(BillingPeriod::dueOn(null, '2026-03-05'))->toBe('2026-03-05');
});

it('reports a negative day offset before the due date', function () {
    expect(BillingPeriod::dayOffset('2026-09-21', '2026-09-18'))->toBe(-3)
        ->and(BillingPeriod::dayOffset('2026-09-18', '2026-09-18'))->toBe(0)
        ->and(BillingPeriod::dayOffset('2026-09-04', '2026-09-18'))->toBe(14);
});

it('counts day offsets across month and year boundaries', function () {
    expect(BillingPeriod::dayOffset('2026-08-30', '2026-09-18'))->toBe(19)
        ->and(BillingPeriod::dayOffset('2025-12-30', '2026-01-05'))->toBe(6);
});
