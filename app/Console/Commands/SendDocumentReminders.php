<?php

namespace App\Console\Commands;

use App\Jobs\SendDocumentReminder;
use App\Models\Member;
use Illuminate\Console\Command;

class SendDocumentReminders extends Command
{
    protected $signature = 'documents:remind
                            {--dry-run : Report what would be sent without sending or logging anything}';

    protected $description = 'Nudge members whose registration document is missing or older than the configured staleness window';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $staleDays = (int) config('membership.document_reminder_stale_days');
        $period = now()->format('Y-m');

        $counts = ['considered' => 0, 'queued' => 0];

        Member::query()
            ->whereNotIn('status', ['cancelled', 'pending'])
            ->orderBy('id')
            ->chunkById(500, function ($members) use ($staleDays, $dryRun, $period, &$counts) {
                foreach ($members as $member) {
                    $counts['considered']++;

                    $isMissing = blank($member->registration_document_path);
                    $isStale = ! $isMissing
                        && $member->registration_document_updated_at?->lt(now()->subDays($staleDays));

                    if (! $isMissing && ! $isStale) {
                        continue;
                    }

                    $counts['queued']++;

                    if ($dryRun) {
                        $this->line(sprintf(
                            '  would remind %-34s (%s)',
                            $member->email, $isMissing ? 'missing' : 'stale',
                        ));

                        continue;
                    }

                    // One slot per member per calendar month — the document
                    // doesn't change day to day, so "once overdue" reminders
                    // like the dues schedule would just repeat the same
                    // message daily with nothing new to report.
                    SendDocumentReminder::dispatch(
                        member: $member,
                        isMissing: $isMissing,
                        dedupeKey: "document_reminder:{$member->id}:{$period}",
                    );
                }
            });

        $this->newLine();
        $this->info(sprintf(
            '%s considered=%d queued=%d',
            $dryRun ? 'Dry run:' : 'Done:',
            $counts['considered'], $counts['queued'],
        ));

        if ($dryRun) {
            $this->comment('Nothing was sent and nothing was logged.');
        }

        return self::SUCCESS;
    }
}
