<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentRecorder
{
    /**
     * Record a payment that arrived by hand (bank transfer, cash, cheque).
     *
     * paid_through is refreshed by a database trigger rather than here, so
     * the member's coverage stays correct even when a payment is later
     * corrected or removed by some other route.
     */
    public function record(Member $member, array $data): Payment
    {
        return DB::transaction(function () use ($member, $data) {
            $months = max(1, (int) ($data['months'] ?? 1));

            // Dates come from the server's own calculation unless explicitly
            // supplied, so a stale browser tab cannot post a period that
            // overlaps cover the member already has.
            if (empty($data['period_start']) || empty($data['period_end'])) {
                $period = BillingPeriod::next(
                    $member->paid_through?->format('Y-m-d'),
                    $member->join_date->format('Y-m-d'),
                    $months,
                );
                $data['period_start'] = $period['period_start'];
                $data['period_end'] = $period['period_end'];
            }

            $payment = $member->payments()->create([
                'amount' => $data['amount'],
                'paid_on' => $data['paid_on'] ?? now()->toDateString(),
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'method' => $data['method'] ?? 'bank_transfer',
                'reference' => $data['reference'] ?? null,
                'note' => $data['note'] ?? null,
                'recorded_by' => $data['recorded_by'] ?? auth()->id(),
            ]);

            // The trigger has updated paid_through behind Eloquent's back.
            $member->refresh();

            // Paying revives a lapsed membership. 'cancelled' is a human
            // decision and is never reversed by money landing: an admin has
            // to reinstate it deliberately.
            if ($member->status === 'lapsed'
                && $member->paid_through !== null
                && $member->dayOffset() < 0) {
                $member->update(['status' => 'active']);
            }

            return $payment;
        });
    }

    /**
     * Preview for the form, so the admin sees the dates before saving.
     *
     * The suggested amount comes from effectiveMonthlyFee(), which falls back
     * to the tier's price when the company has no negotiated rate of its own.
     * Reading $member->monthly_fee directly here would suggest zero for every
     * company on a standard rate.
     */
    public function preview(Member $member, int $months = 1): array
    {
        $member->loadMissing('memberType');

        $period = BillingPeriod::next(
            $member->paid_through?->format('Y-m-d'),
            $member->join_date->format('Y-m-d'),
            $months,
        );

        $period['suggested_amount'] = round($member->effectiveMonthlyFee() * max(1, $months), 2);

        return $period;
    }
}
