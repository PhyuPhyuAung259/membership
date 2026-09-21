<?php

namespace App\Console\Commands;

use App\Jobs\SendDuesReminder;
use App\Models\Member;
use App\Services\ReminderSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendDuesReminders extends Command
{
    protected $signature = 'dues:remind
                            {--dry-run : Report what would be sent without sending or logging anything}
                            {--no-lapse : Do not mark anyone lapsed on this run}';

    protected $description = 'Queue dues reminders that fall due today and lapse members past their final notice';

    public function handle(ReminderSchedule $schedule): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $counts = ['considered' => 0, 'queued' => 0, 'lapsed' => 0];
        $stages = [];

        Member::query()
            ->where('status', '!=', 'cancelled')
            ->orderBy('id')
            ->chunkById(500, function ($members) use ($schedule, $dryRun, &$counts, &$stages) {
                foreach ($members as $member) {
                    $counts['considered']++;

                    $plan = $schedule->decide($member->dayOffset());

                    if ($plan === null) {
                        continue;
                    }

                    $stages[$plan['stage']] = ($stages[$plan['stage']] ?? 0) + 1;
                    $counts['queued']++;

                    if ($dryRun) {
                        $this->line(sprintf(
                            '  would send %-8s to %-34s (due %s, %d days)',
                            $plan['stage'], $member->email, $member->dueOn(), $plan['days_overdue'],
                        ));

                        continue;
                    }

                    SendDuesReminder::dispatch(
                        member: $member,
                        stage: $plan['stage'],
                        slot: $plan['slot'],
                        dueOn: $member->dueOn(),
                        daysOverdue: $plan['days_overdue'],
                        dedupeKey: $schedule->dedupeKey($member->id, $member->dueOn(), $plan['slot']),
                    );
                }
            });

        if (! $dryRun && ! $this->option('no-lapse')) {
            $counts['lapsed'] = $this->lapse($schedule->lapseAfterDays());
        }

        $this->newLine();
        $this->info(sprintf(
            '%s considered=%d queued=%d lapsed=%d stages(%s)',
            $dryRun ? 'Dry run:' : 'Done:',
            $counts['considered'], $counts['queued'], $counts['lapsed'],
            collect($stages)->map(fn ($n, $s) => "{$s}={$n}")->implode(' ') ?: 'none',
        ));

        if ($dryRun) {
            $this->comment('Nothing was sent and nothing was logged.');
        }

        return self::SUCCESS;
    }

    /**
     * Members chased past the final notice stop receiving mail and go lapsed.
     * Done in SQL rather than per-model so one query settles it.
     */
    private function lapse(int $lapseAfterDays): int
    {
        return DB::table('members')
            ->where('status', 'active')
            ->whereRaw(
                'CURRENT_DATE - COALESCE(paid_through + INTERVAL \'1 day\', join_date)::date > ?',
                [$lapseAfterDays],
            )
            ->update(['status' => 'lapsed', 'updated_at' => now()]);
    }
}
