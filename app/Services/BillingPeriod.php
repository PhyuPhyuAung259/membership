<?php

namespace App\Services;

use DateTimeImmutable;

/**
 * Works out which period the next payment covers.
 *
 * Uses native DateTimeImmutable rather than Carbon for one reason: PHP's
 * built-in "+1 month" overflows. 31 January + 1 month gives 3 March, not
 * 28 February, and Carbon's plain addMonth() inherits that behaviour. Only
 * addMonthsNoOverflow() clamps. Rather than depend on remembering which
 * Carbon method is the safe one, the clamping is done explicitly here and
 * covered by tests.
 *
 * Carbon instances extend DateTimeInterface, so callers can pass Carbon in
 * and convert the result back freely.
 */
class BillingPeriod
{
    /**
     * @return array{period_start: string, period_end: string, months: int}
     */
    public static function next(?string $paidThrough, string $joinDate, int $months = 1): array
    {
        $months = max(1, $months);

        // Coverage is continuous: a new period starts the day after the last
        // one ended, not today. A member who pays three weeks late is still
        // credited from the day their cover lapsed, so they do not lose the
        // time they were chased for, and they do not quietly get a free
        // stretch either. A member who has never paid starts at their join
        // date.
        $start = $paidThrough !== null
            ? self::date($paidThrough)->modify('+1 day')
            : self::date($joinDate);

        $end = self::addMonths($start, $months)->modify('-1 day');

        return [
            'period_start' => $start->format('Y-m-d'),
            'period_end' => $end->format('Y-m-d'),
            'months' => $months,
        ];
    }

    /**
     * Add whole months, clamping to the last day of the target month instead
     * of spilling into the next one.
     *
     * 31 Jan + 1 month => 28 Feb (29 Feb in a leap year), not 3 March.
     */
    public static function addMonths(DateTimeImmutable $date, int $months): DateTimeImmutable
    {
        $day = (int) $date->format('j');

        // Move on the first of the month, where no overflow is possible, then
        // put the day back, capped at the length of the month we landed in.
        $firstOfTarget = $date->modify('first day of this month')->modify("+{$months} months");
        $daysInTarget = (int) $firstOfTarget->format('t');

        return $firstOfTarget->setDate(
            (int) $firstOfTarget->format('Y'),
            (int) $firstOfTarget->format('n'),
            min($day, $daysInTarget),
        );
    }

    /** Day after coverage ends: the first day a member is not paid for. */
    public static function dueOn(?string $paidThrough, string $joinDate): string
    {
        return $paidThrough !== null
            ? self::date($paidThrough)->modify('+1 day')->format('Y-m-d')
            : self::date($joinDate)->format('Y-m-d');
    }

    /** Whole days from the due date to today. Negative means not due yet. */
    public static function dayOffset(string $dueOn, ?string $today = null): int
    {
        $due = self::date($dueOn);
        $now = self::date($today ?? date('Y-m-d'));

        return (int) $due->diff($now)->format('%r%a');
    }

    private static function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable(substr($value, 0, 10) . ' 00:00:00');
    }
}
