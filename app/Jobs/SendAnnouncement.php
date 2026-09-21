<?php

namespace App\Jobs;

use App\Mail\Announcement;
use App\Models\Broadcast;
use App\Models\Member;
use App\Services\LoggedMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * One job per member rather than one job looping over everybody.
 *
 * A single job sending to 800 members fails as a unit: one bad address near
 * the start and the retry re-sends to everyone before it. Per-member jobs
 * isolate failures, let the queue pace itself, and give honest progress
 * counts on the compose screen.
 */
class SendAnnouncement implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(
        public Member $member,
        public Broadcast $broadcast,
    ) {}

    public function handle(LoggedMailer $mailer): void
    {
        $dedupeKey = "broadcast:{$this->broadcast->id}:member:{$this->member->id}";
        $allowed = $this->member->canReceive('announcement');

        if (! $allowed['ok']) {
            $mailer->skip(
                kind: 'announcement',
                dedupeKey: $dedupeKey,
                subject: $this->broadcast->subject,
                reason: $allowed['reason'],
                memberId: $this->member->id,
                toEmail: $this->member->email,
            );

            return;
        }

        $result = $mailer->send(
            toEmail: $this->member->email,
            mailable: new Announcement($this->member, $this->broadcast),
            kind: 'announcement',
            dedupeKey: $dedupeKey,
            subject: $this->broadcast->subject,
            memberId: $this->member->id,
            broadcastId: $this->broadcast->id,
        );

        // Atomic increments: many workers may finish at the same moment, and
        // read-modify-write would lose counts.
        $column = match ($result) {
            LoggedMailer::RESULT_SENT => 'sent_count',
            LoggedMailer::RESULT_FAILED => 'failed_count',
            default => null,
        };

        if ($column) {
            DB::table('broadcasts')->where('id', $this->broadcast->id)->increment($column);
        }
    }
}
