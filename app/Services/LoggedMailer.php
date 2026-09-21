<?php

namespace App\Services;

use App\Models\EmailLog;
use Illuminate\Database\QueryException;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Sends mail, but logs it first.
 *
 * Every message inserts an email_log row carrying a UNIQUE dedupe_key BEFORE
 * the mailer is called. A retried queue job, a double-clicked Send, or two
 * workers racing all collide on that insert, and the loser returns without
 * contacting the provider.
 *
 * This matters more under Laravel than it would in a plain script, because
 * the queue retries failed jobs for you. Without the constraint, one timeout
 * from your mail provider becomes two identical reminders in a member's
 * inbox, and the member has no way to know which one to believe.
 *
 * The tradeoff is deliberate and one-directional: if the worker dies between
 * the insert and the send, the row stays 'queued' and is NEVER retried
 * automatically. Under-sending one reminder is recoverable by a human looking
 * at the stuck list on the dashboard. Double-sending cannot be taken back.
 */
class LoggedMailer
{
    public const RESULT_SENT = 'sent';
    public const RESULT_DUPLICATE = 'duplicate';
    public const RESULT_FAILED = 'failed';

    public function send(
        string $toEmail,
        Mailable $mailable,
        string $kind,
        string $dedupeKey,
        string $subject,
        ?int $memberId = null,
        ?int $broadcastId = null,
    ): string {
        try {
            // Wrapped in its own transaction so a unique-violation here only
            // rolls back to a savepoint. Postgres otherwise aborts the whole
            // enclosing transaction on a failed statement, which would take
            // down every query after it (the caller's, a test's, anything).
            $log = DB::transaction(fn () => EmailLog::create([
                'member_id' => $memberId,
                'to_email' => $toEmail,
                'kind' => $kind,
                'dedupe_key' => $dedupeKey,
                'subject' => $subject,
                'broadcast_id' => $broadcastId,
                'status' => 'queued',
                'mailer' => config('mail.default'),
            ]));
        } catch (QueryException $e) {
            // 23505 is Postgres' unique violation. Anything else is a real
            // problem and must not be swallowed.
            if ($e->getCode() === '23505') {
                return self::RESULT_DUPLICATE;
            }

            throw $e;
        }

        try {
            Mail::to($toEmail)->send($mailable);

            $log->update(['status' => 'sent', 'sent_at' => now()]);

            return self::RESULT_SENT;
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 500),
            ]);

            return self::RESULT_FAILED;
        }
    }

    /** Record a decision not to send, so the audit trail explains the gap. */
    public function skip(
        string $kind,
        string $dedupeKey,
        string $subject,
        string $reason,
        ?int $memberId = null,
        ?string $toEmail = null,
    ): void {
        try {
            DB::transaction(fn () => EmailLog::create([
                'member_id' => $memberId,
                'to_email' => $toEmail ?? '',
                'kind' => $kind,
                'dedupe_key' => $dedupeKey,
                'subject' => $subject,
                'status' => 'skipped',
                'error' => $reason,
            ]));
        } catch (QueryException $e) {
            if ($e->getCode() !== '23505') {
                throw $e;
            }
        }
    }
}
