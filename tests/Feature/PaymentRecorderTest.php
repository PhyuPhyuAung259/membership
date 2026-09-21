<?php

use App\Models\Member;
use App\Models\Payment;
use App\Services\PaymentRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function member(array $attrs = []): Member
{
    return Member::create(array_merge([
        'company_name' => 'Aisha Rahman',
        'email' => 'aisha' . uniqid() . '@test.invalid',
        'monthly_fee' => 25,
        'join_date' => '2026-01-01',
    ], $attrs));
}

it('moves coverage to the end of the period paid for', function () {
    $m = member();

    app(PaymentRecorder::class)->record($m, ['months' => 1, 'amount' => 25]);

    expect($m->refresh()->paid_through->format('Y-m-d'))->toBe('2026-01-31');
});

it('lets the database trigger own paid_through', function () {
    // Written directly, bypassing Eloquent events entirely. An observer would
    // miss this; the trigger cannot.
    $m = member();

    DB::table('payments')->insert([
        'member_id' => $m->id, 'amount' => 25, 'paid_on' => '2026-01-02',
        'period_start' => '2026-01-01', 'period_end' => '2026-01-31',
        'method' => 'cash', 'created_at' => now(), 'updated_at' => now(),
    ]);

    expect($m->refresh()->paid_through->format('Y-m-d'))->toBe('2026-01-31');
});

it('pulls coverage back when a mistaken payment is removed', function () {
    $m = member();
    $recorder = app(PaymentRecorder::class);

    $recorder->record($m, ['months' => 1, 'amount' => 25]);
    $second = $recorder->record($m->refresh(), ['months' => 1, 'amount' => 25]);

    expect($m->refresh()->paid_through->format('Y-m-d'))->toBe('2026-02-28');

    $second->delete();

    expect($m->refresh()->paid_through->format('Y-m-d'))->toBe('2026-01-31');
});

it('records a multi-month payment as one row', function () {
    $m = member();

    $payment = app(PaymentRecorder::class)->record($m, ['months' => 3, 'amount' => 75]);

    expect(Payment::count())->toBe(1)
        ->and($payment->period_end->format('Y-m-d'))->toBe('2026-03-31');
});

it('revives a lapsed membership when payment arrives', function () {
    $m = member(['status' => 'lapsed', 'join_date' => now()->subMonths(2)->toDateString()]);

    app(PaymentRecorder::class)->record($m, ['months' => 6, 'amount' => 150]);

    expect($m->refresh()->status)->toBe('active');
});

it('never revives a cancelled membership', function () {
    // Cancellation is a human decision. Money landing must not undo it; an
    // admin has to reinstate deliberately.
    $m = member(['status' => 'cancelled']);

    app(PaymentRecorder::class)->record($m, ['months' => 12, 'amount' => 300]);

    expect($m->refresh()->status)->toBe('cancelled');
});

it('suggests the right amount and dates for the form', function () {
    $m = member();
    $recorder = app(PaymentRecorder::class);

    expect($recorder->preview($m, 3))
        ->toMatchArray([
            'period_start' => '2026-01-01',
            'period_end' => '2026-03-31',
            'suggested_amount' => 75.0,
        ]);
});

it('refuses a period that ends before it starts', function () {
    $m = member();

    expect(fn () => $m->payments()->create([
        'amount' => 25, 'paid_on' => '2026-01-01',
        'period_start' => '2026-03-01', 'period_end' => '2026-02-01',
    ]))->toThrow(Illuminate\Database\QueryException::class);
});
