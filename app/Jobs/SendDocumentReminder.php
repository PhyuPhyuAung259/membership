<?php

namespace App\Jobs;

use App\Mail\DocumentReminder;
use App\Models\Member;
use App\Services\LoggedMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendDocumentReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(
        public Member $member,
        public bool $isMissing,
        public string $dedupeKey,
    ) {}

    public function handle(LoggedMailer $mailer): void
    {
        // Treated as transactional, like dues reminders: this is about
        // compliance paperwork, not marketing, so it goes out regardless of
        // the announcements opt-in.
        $allowed = $this->member->canReceive('document_reminder');

        $mailable = new DocumentReminder($this->member, $this->isMissing);
        $subject = $mailable->envelope()->subject;

        if (! $allowed['ok']) {
            $mailer->skip(
                kind: 'document_reminder',
                dedupeKey: $this->dedupeKey,
                subject: $subject,
                reason: $allowed['reason'],
                memberId: $this->member->id,
                toEmail: $this->member->email,
            );

            return;
        }

        $mailer->send(
            toEmail: $this->member->email,
            mailable: $mailable,
            kind: 'document_reminder',
            dedupeKey: $this->dedupeKey,
            subject: $subject,
            memberId: $this->member->id,
        );
    }
}
