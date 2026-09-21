<?php

namespace App\Services;

/**
 * Decides which dues reminder, if any, a member should receive today.
 *
 * Deliberately free of any framework dependency: no Eloquent, no Carbon, no
 * facades. It takes a day offset and returns a decision. That makes the rules
 * that decide whether someone gets chased for money unit-testable without a
 * database, which matters because a mistake here either spams your members or
 * silently stops collecting, and neither shows up in the interface.
 */
class ReminderSchedule
{
    public const STAGE_UPCOMING = 'upcoming';
    public const STAGE_DUE = 'due';
    public const STAGE_OVERDUE = 'overdue';
    public const STAGE_FINAL = 'final';

    /** @var int[] */
    private array $beforeDays;

    /** @var int[] */
    private array $afterDays;

    private int $graceDays;
    private int $lapseAfterDays;

    public function __construct(array $config)
    {
        $this->beforeDays = array_map('intval', $config['before_days'] ?? [3]);
        $this->afterDays = array_map('intval', $config['after_days'] ?? [0, 7, 14]);
        $this->graceDays = (int) ($config['grace_days'] ?? 3);
        $this->lapseAfterDays = (int) ($config['lapse_after_days'] ?? 14);
    }

    /**
     * @param  int  $dayOffset  Today relative to the due date. Negative means
     *                          the payment is not due yet.
     * @return array{stage: string, slot: string, days_overdue: int}|null
     *         Null when today is not one of this member's reminder days.
     */
    public function decide(int $dayOffset): ?array
    {
        if ($dayOffset < 0) {
            $daysUntil = -$dayOffset;

            if (! in_array($daysUntil, $this->beforeDays, true)) {
                return null;
            }

            return $this->plan(self::STAGE_UPCOMING, "before{$daysUntil}", 0);
        }

        // Past the final notice the membership lapses and chasing stops for
        // good. Continuing to email someone who has plainly gone is how an
        // association ends up on a blocklist.
        if ($dayOffset > $this->lapseAfterDays) {
            return null;
        }

        if (! in_array($dayOffset, $this->afterDays, true)) {
            return null;
        }

        // The notice on the due date itself always goes out: "your payment is
        // due today" is information, not an accusation. Chasing emails are
        // held back until the grace period has passed, which covers the lag
        // between a member transferring money and an admin keying it in.
        if ($dayOffset > 0 && $dayOffset < $this->graceDays) {
            return null;
        }

        if ($dayOffset === 0) {
            return $this->plan(self::STAGE_DUE, 'due', 0);
        }

        if ($dayOffset >= $this->lapseAfterDays) {
            return $this->plan(self::STAGE_FINAL, 'final', $dayOffset);
        }

        return $this->plan(self::STAGE_OVERDUE, "after{$dayOffset}", $dayOffset);
    }

    /**
     * The key that stops a reminder being sent twice.
     *
     * Tied to the specific unpaid period rather than the calendar month, so a
     * member who pays and later falls behind again is chaseable, while the
     * same slot within one period can only ever send once.
     */
    public function dedupeKey(int $memberId, string $dueOn, string $slot): string
    {
        return "dues:{$memberId}:{$dueOn}:{$slot}";
    }

    public function lapseAfterDays(): int
    {
        return $this->lapseAfterDays;
    }

    private function plan(string $stage, string $slot, int $daysOverdue): array
    {
        return ['stage' => $stage, 'slot' => $slot, 'days_overdue' => $daysOverdue];
    }
}
