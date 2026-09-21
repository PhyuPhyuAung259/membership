<?php

use App\Jobs\SendDuesReminder;
use App\Mail\DuesReminder;
use App\Models\EmailLog;
use App\Models\Member;
use App\Services\LoggedMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function overdueMember(int $daysOverdue, array $attrs = []): Member
{
    $m = Member::create(array_merge([
        'company_name' => 'Late Payer',
        'email' => 'late' . uniqid() . '@test.invalid',
        'monthly_fee' => 25,
        'join_date' => now()->subYear()->toDateString(),
    ], $attrs));

    $m->payments()->create([
        'amount' => 25,
        'paid_on' => now()->subMonths(2)->toDateString(),
        'period_start' => now()->subMonths(2)->toDateString(),
        'period_end' => now()->subDays($daysOverdue + 1)->toDateString(),
    ]);

    return $m->refresh();
}

it('queues a reminder for a member on a scheduled day', function () {
    Queue::fake();
    overdueMember(7);

    $this->artisan('dues:remind')->assertSuccessful();

    Queue::assertPushed(SendDuesReminder::class, 1);
});

it('queues nothing on an unscheduled day', function () {
    Queue::fake();
    overdueMember(5);

    $this->artisan('dues:remind')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('never chases a cancelled member', function () {
    Queue::fake();
    overdueMember(7, ['status' => 'cancelled']);

    $this->artisan('dues:remind')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('sends nothing and logs nothing on a dry run', function () {
    Queue::fake();
    overdueMember(7);

    $this->artisan('dues:remind', ['--dry-run' => true])->assertSuccessful();

    Queue::assertNothingPushed();
    expect(EmailLog::count())->toBe(0);
});

it('marks a member lapsed once they are past the final notice', function () {
    Queue::fake();
    $m = overdueMember(20);

    $this->artisan('dues:remind')->assertSuccessful();

    expect($m->refresh()->status)->toBe('lapsed');
});

it('emails a member only once even when the job runs twice', function () {
    // The queue retries failed jobs automatically, so this constraint is what
    // stops one provider timeout becoming two identical reminders.
    Mail::fake();
    $m = overdueMember(7);

    $this->artisan('dues:remind')->assertSuccessful();
    $this->artisan('dues:remind')->assertSuccessful();

    Mail::assertSent(DuesReminder::class, 1);
    expect(EmailLog::where('member_id', $m->id)->where('status', 'sent')->count())->toBe(1);
});

it('still sends dues reminders to someone who unsubscribed from announcements', function () {
    // Dues notices are transactional: they concern money owed under an
    // existing agreement, so opting out of announcements must not stop them.
    Mail::fake();
    $m = overdueMember(7);
    $m->update(['marketing_opt_in' => false, 'unsubscribed_at' => now()]);

    $this->artisan('dues:remind')->assertSuccessful();

    Mail::assertSent(DuesReminder::class, 1);
});

it('records a skip with its reason instead of sending', function () {
    Mail::fake();
    $m = overdueMember(7, ['status' => 'cancelled']);
    $m->update(['status' => 'active']);

    // Force the consent check to refuse by removing the address.
    DB::table('members')->where('id', $m->id)->update(['email' => '']);

    $this->artisan('dues:remind')->assertSuccessful();

    Mail::assertNothingSent();
    expect(EmailLog::where('member_id', $m->id)->first()?->status)->toBe('skipped');
});

it('returns duplicate rather than sending again for the same dedupe key', function () {
    Mail::fake();
    $m = overdueMember(7);
    $mailer = app(LoggedMailer::class);

    $args = [
        'toEmail' => $m->email,
        'mailable' => new DuesReminder($m, 'overdue', $m->dueOn(), 7),
        'kind' => 'dues_reminder',
        'dedupeKey' => 'dues:test:fixed-key',
        'subject' => 'Test',
        'memberId' => $m->id,
    ];

    expect($mailer->send(...$args))->toBe(LoggedMailer::RESULT_SENT)
        ->and($mailer->send(...$args))->toBe(LoggedMailer::RESULT_DUPLICATE);

    Mail::assertSent(DuesReminder::class, 1);
});
