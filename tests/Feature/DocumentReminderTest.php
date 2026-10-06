<?php

use App\Jobs\SendDocumentReminder;
use App\Models\EmailLog;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function docMember(array $attrs = []): Member
{
    return Member::create(array_merge([
        'company_name' => 'Doc Co',
        'email' => 'doc' . uniqid() . '@test.invalid',
        'join_date' => now()->subYears(2)->toDateString(),
    ], $attrs));
}

it('queues a reminder for a member with no document at all', function () {
    Queue::fake();
    docMember();

    $this->artisan('documents:remind')->assertSuccessful();

    Queue::assertPushed(SendDocumentReminder::class, fn ($job) => $job->isMissing === true);
});

it('queues a reminder for a member whose document is older than the stale window', function () {
    Queue::fake();
    docMember([
        'registration_document_path' => 'registrations/old.pdf',
        'registration_document_updated_at' => now()->subDays(400),
    ]);

    $this->artisan('documents:remind')->assertSuccessful();

    Queue::assertPushed(SendDocumentReminder::class, fn ($job) => $job->isMissing === false);
});

it('queues nothing for a member with a recent document', function () {
    Queue::fake();
    docMember([
        'registration_document_path' => 'registrations/fresh.pdf',
        'registration_document_updated_at' => now()->subDays(10),
    ]);

    $this->artisan('documents:remind')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('never reminds a cancelled or pending member', function () {
    Queue::fake();
    docMember(['status' => 'cancelled']);
    docMember(['status' => 'pending']);

    $this->artisan('documents:remind')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('sends nothing and logs nothing on a dry run', function () {
    Queue::fake();
    docMember();

    $this->artisan('documents:remind --dry-run')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('sends once per member per month, not once per run', function () {
    $member = docMember();

    $this->artisan('documents:remind')->assertSuccessful();
    $this->artisan('documents:remind')->assertSuccessful();

    expect(EmailLog::where('kind', 'document_reminder')->where('member_id', $member->id)->count())->toBe(1);
});

it('still reminds a member who unsubscribed from announcements', function () {
    $member = docMember(['unsubscribed_at' => now(), 'marketing_opt_in' => false]);

    $this->artisan('documents:remind')->assertSuccessful();

    expect(EmailLog::where('kind', 'document_reminder')->where('member_id', $member->id)->where('status', 'sent')->exists())
        ->toBeTrue();
});
