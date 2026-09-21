<?php

use App\Services\ReminderSchedule;

/*
| The rules that decide whether a member gets chased for money. No database
| and no framework: a mistake here either spams your members or silently
| stops collecting, and neither shows up in the interface.
*/

function schedule(array $overrides = []): ReminderSchedule
{
    return new ReminderSchedule(array_merge([
        'before_days' => [3],
        'after_days' => [0, 7, 14],
        'grace_days' => 3,
        'lapse_after_days' => 14,
    ], $overrides));
}

it('sends an advance notice on the configured day before the due date', function () {
    expect(schedule()->decide(-3)['stage'])->toBe('upcoming');
});

it('sends nothing on unscheduled days before the due date', function (int $offset) {
    expect(schedule()->decide($offset))->toBeNull();
})->with([-10, -5, -4, -2, -1]);

it('always sends the due-date notice, regardless of the grace period', function () {
    // "Your payment is due today" is information, not an accusation, so the
    // grace period must never suppress it.
    $plan = schedule(['after_days' => [0, 2, 7]])->decide(0);

    expect($plan['stage'])->toBe('due')
        ->and($plan['days_overdue'])->toBe(0);
});

it('holds back a chasing email scheduled inside the grace period', function () {
    // Covers the gap between a member transferring money and an admin keying
    // it in. Chasing someone on day 2 for money that arrived on day 1 is how
    // you get angry replies.
    expect(schedule(['after_days' => [0, 2, 7]])->decide(2))->toBeNull();
});

it('sends the chase once the grace period has passed', function () {
    $plan = schedule()->decide(7);

    expect($plan['stage'])->toBe('overdue')
        ->and($plan['days_overdue'])->toBe(7);
});

it('treats the last scheduled day as a final notice, not another chase', function () {
    expect(schedule()->decide(14)['stage'])->toBe('final');
});

it('stops chasing entirely after the final notice', function (int $offset) {
    // Continuing to email someone who has plainly gone is how an association
    // ends up on a blocklist.
    expect(schedule()->decide($offset))->toBeNull();
})->with([15, 20, 60, 400]);

it('sends nothing on days that are not scheduled at all', function (int $offset) {
    expect(schedule()->decide($offset))->toBeNull();
})->with([1, 2, 3, 5, 6, 8, 13]);

it('gives every slot in a period a distinct dedupe key', function () {
    $s = schedule();
    $keys = collect([-3, 0, 7, 14])
        ->map(fn ($o) => $s->dedupeKey(42, '2026-10-01', $s->decide($o)['slot']))
        ->unique();

    expect($keys)->toHaveCount(4);
});

it('produces a stable key for the same slot in the same period', function () {
    $s = schedule();

    expect($s->dedupeKey(42, '2026-10-01', 'after7'))
        ->toBe($s->dedupeKey(42, '2026-10-01', 'after7'));
});

it('produces a fresh key for a new unpaid period so reminders can resume', function () {
    // A member who pays and later falls behind again must be chaseable.
    $s = schedule();

    expect($s->dedupeKey(42, '2026-10-01', 'after7'))
        ->not->toBe($s->dedupeKey(42, '2026-11-01', 'after7'));
});

it('never shares a key between two members', function () {
    $s = schedule();

    expect($s->dedupeKey(1, '2026-10-01', 'due'))->not->toBe($s->dedupeKey(2, '2026-10-01', 'due'));
});
