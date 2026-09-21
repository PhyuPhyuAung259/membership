<?php

use App\Jobs\SendAnnouncement;
use App\Mail\Announcement;
use App\Models\Broadcast;
use App\Models\EmailLog;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function subscriber(array $attrs = []): Member
{
    return Member::create(array_merge([
        'company_name' => 'Member',
        'email' => 'sub' . uniqid() . '@test.invalid',
        'monthly_fee' => 25,
        'join_date' => now()->subMonth()->toDateString(),
    ], $attrs));
}

it('excludes opted-out members from the audience', function () {
    subscriber();
    subscriber(['marketing_opt_in' => false]);
    subscriber(['unsubscribed_at' => now()]);
    subscriber(['status' => 'cancelled']);

    expect(Member::audience('all')->count())->toBe(1);
});

it('sends an announcement to an opted-in member', function () {
    Mail::fake();
    $m = subscriber();
    $broadcast = Broadcast::create(['subject' => 'Monthly meeting', 'body' => 'Hello.', 'audience' => 'all']);

    (new SendAnnouncement($m, $broadcast))->handle(app(App\Services\LoggedMailer::class));

    Mail::assertSent(Announcement::class, 1);
    expect($broadcast->refresh()->sent_count)->toBe(1);
});

it('skips an unsubscribed member and records why', function () {
    Mail::fake();
    $m = subscriber(['unsubscribed_at' => now(), 'marketing_opt_in' => false]);
    $broadcast = Broadcast::create(['subject' => 'News', 'body' => 'Hello.', 'audience' => 'all']);

    (new SendAnnouncement($m, $broadcast))->handle(app(App\Services\LoggedMailer::class));

    Mail::assertNothingSent();
    expect(EmailLog::where('member_id', $m->id)->first()->status)->toBe('skipped');
});

it('never sends the same announcement to a member twice', function () {
    Mail::fake();
    $m = subscriber();
    $broadcast = Broadcast::create(['subject' => 'News', 'body' => 'Hello.', 'audience' => 'all']);
    $job = new SendAnnouncement($m, $broadcast);

    $job->handle(app(App\Services\LoggedMailer::class));
    $job->handle(app(App\Services\LoggedMailer::class));

    Mail::assertSent(Announcement::class, 1);
});
