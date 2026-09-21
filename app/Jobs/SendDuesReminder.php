<?php

namespace App\Jobs;

use App\Mail\DuesReminder;
use App\Models\Member;
use App\Services\LoggedMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendDuesReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(
        public Member $member,
        public string $stage,
        public string $slot,
        public string $dueOn,
        public int $daysOverdue,
        public string $dedupeKey,
    ) {}

    public function handle(LoggedMailer $mailer): void
    {
        $allowed = $this->member->canReceive('dues_reminder');

        $mailable = new DuesReminder($this->member, $this->stage, $this->dueOn, $this->daysOverdue);
        $subject = $mailable->envelope()->subject;

        if (! $allowed['ok']) {
            $mailer->skip(
                kind: 'dues_reminder',
                dedupeKey: $this->dedupeKey,
                subject: $subject,
                reason: $allowed['reason'],
                memberId: $this->member->id,
                toEmail: $this->member->email,
            );

            return;
        }

        // Retries are safe: the dedupe key means a second attempt after a
        // successful send is a no-op rather than a second email.
        $mailer->send(
            toEmail: $this->member->email,
            mailable: $mailable,
            kind: 'dues_reminder',
            dedupeKey: $this->dedupeKey,
            subject: $subject,
            memberId: $this->member->id,
        );
    }
}
