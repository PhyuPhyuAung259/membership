<?php

namespace App\Mail;

use App\Models\Member;
use App\Services\ReminderSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DuesReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Member $member,
        public string $stage,
        public string $dueOn,
        public int $daysOverdue,
    ) {}

    public function envelope(): Envelope
    {
        $due = $this->formatDate($this->dueOn);

        $subject = match ($this->stage) {
            ReminderSchedule::STAGE_UPCOMING => "Your membership payment is due {$due}",
            ReminderSchedule::STAGE_DUE => 'Membership payment due today',
            ReminderSchedule::STAGE_OVERDUE => "Membership payment overdue — {$this->daysOverdue} days",
            ReminderSchedule::STAGE_FINAL => 'Final notice: membership payment overdue',
            default => 'Membership payment',
        };

        return new Envelope(
            subject: $subject,
            replyTo: config('membership.reply_to')
                ? [new Address(config('membership.reply_to'))]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.dues-reminder',
            with: [
                'heading' => match ($this->stage) {
                    ReminderSchedule::STAGE_UPCOMING => 'Payment due soon',
                    ReminderSchedule::STAGE_DUE => 'Payment due',
                    ReminderSchedule::STAGE_OVERDUE => 'Payment overdue',
                    ReminderSchedule::STAGE_FINAL => 'Final notice',
                    default => 'Membership payment',
                },
                'lead' => $this->lead(),
                'instructions' => config('membership.payment_instructions'),
            ],
        );
    }

    private function lead(): string
    {
        // Greet the contact person where we have one, falling back to the
        // company. The member is an organisation, so "Hello Acme Trading" is
        // the right shape when no contact is recorded.
        $greeting = $this->member->greetingName();

        $company = $this->member->company_name;
        $due = $this->formatDate($this->dueOn);
        $symbol = config('membership.currency_symbol');

        // The tier's price unless the company is on a negotiated rate.
        $this->member->loadMissing('memberType');
        $fee = $this->member->effectiveMonthlyFee();
        $amount = $fee > 0 ? " The monthly amount is {$symbol}" . number_format($fee, 2) . '.' : '';

        return match ($this->stage) {
            ReminderSchedule::STAGE_UPCOMING => "Hello {$greeting}, the membership for {$company} is paid up to "
                . $this->formatDate($this->member->paid_through?->format('Y-m-d'))
                . ". The next payment is due on {$due}.{$amount}",

            ReminderSchedule::STAGE_DUE => "Hello {$greeting}, the membership payment for {$company} "
                . "was due on {$due}.{$amount}",

            ReminderSchedule::STAGE_OVERDUE => "Hello {$greeting}, we have not recorded the payment for "
                . "{$company} that was due on {$due}, now {$this->daysOverdue} days ago.{$amount}",

            ReminderSchedule::STAGE_FINAL => "Hello {$greeting}, the payment for {$company} due on {$due} "
                . "is still outstanding after {$this->daysOverdue} days.{$amount} If we do not hear from you, "
                . 'the membership will be marked inactive and you will stop receiving member updates.',

            default => "Hello {$greeting}, this is a notice about the membership payment for {$company}.",
        };
    }

    private function formatDate(?string $iso): string
    {
        return $iso ? date('j F Y', strtotime($iso)) : '';
    }
}
