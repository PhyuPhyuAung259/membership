<div>
    <header class="mb-6">
        <div class="mb-1 text-[.8125rem] text-[var(--color-ink-2)]">{{ now()->format('j F Y') }}</div>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-semibold tracking-tight">Collections</h1>
            <div class="flex flex-wrap gap-2">
                <button wire:click="previewReminders" class="btn btn-quiet" wire:loading.attr="disabled">
                    See who would be emailed
                </button>
                <button wire:click="runReminders" class="btn"
                        wire:confirm="Queue all reminders due today? Members already emailed for this period will not be emailed again."
                        wire:loading.attr="disabled">
                    Send reminders now
                </button>
            </div>
        </div>
    </header>

    @if (config('mail.default') === 'log')
        <div class="notice">
            <strong>Email is going to the log, not to members.</strong>
            Messages are written to <code>storage/logs/laravel.log</code>. Set <code>MAIL_MAILER</code>
            in <code>.env</code> when you are ready to send for real.
        </div>
    @endif

    @if ($this->stuck->isNotEmpty())
        <div class="notice notice-bad">
            <strong>{{ $this->stuck->count() }} message{{ $this->stuck->count() === 1 ? '' : 's' }} never finished sending.</strong>
            A queue worker died mid-send. Nothing is retried automatically, so these need a look —
            re-running reminders or resending the announcement is safe and will not duplicate anything.
        </div>
    @endif

    @if ($reminderReport)
        <div class="notice">
            <strong>Reminder run</strong>
            <pre class="mt-2 overflow-x-auto whitespace-pre-wrap text-xs">{{ $reminderReport }}</pre>
        </div>
    @endif

    <div class="tally">
        <div><b class="text-[var(--color-paid)]">{{ $this->counts['current'] }}</b><span>paid up</span></div>
        <div><b>{{ $this->counts['due_soon'] }}</b><span>due within 7 days</span></div>
        <div><b class="text-[var(--color-stamp)]">{{ $this->counts['overdue'] }}</b><span>overdue</span></div>
        <div><b>{{ $this->counts['lapsed'] }}</b><span>lapsed</span></div>
        <div><b>{{ $this->counts['total'] }}</b><span>on the register</span></div>
    </div>

    <section @class(['panel', 'is-late' => $this->overdueMembers->isNotEmpty()])>
        <header>
            <h2 class="text-[1.0625rem] font-semibold">Overdue</h2>
            <span class="hint">Chase these, or record a payment if it has arrived</span>
        </header>
        <div class="overflow-x-auto">
            <table class="ledger">
                <thead>
                    <tr>
                        <th>Member</th><th>Paid through</th><th>Was due</th>
                        <th class="num">Days late</th><th class="num">Monthly</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->overdueMembers as $member)
                        <tr>
                            <td>
                                <a href="{{ route('members', ['filter' => 'overdue', 'search' => $member->email]) }}"
                                   class="font-medium underline decoration-[var(--color-rule)] hover:decoration-[var(--color-ink)]">{{ $member->company_name }}</a>
                                <div class="text-[.8125rem] text-[var(--color-ink-2)]">{{ $member->email }}</div>
                            </td>
                            <td>
                                @if ($member->paid_through)
                                    {{ $member->paid_through->format('j M Y') }}
                                @else
                                    <span class="mark mark-never_paid">Never paid</span>
                                @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($member->dueOn())->format('j M Y') }}</td>
                            <td class="num"><span class="late-days">{{ $member->daysOverdue() }}</span></td>
                            <td class="num">{{ config('membership.currency_symbol') }}{{ number_format($member->effectiveMonthlyFee(), 2) }}</td>
                            <td></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty"><strong>Nobody is overdue.</strong>Every member is paid up to date.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <header>
            <h2 class="text-[1.0625rem] font-semibold">Due within 7 days</h2>
            <span class="hint">Advance notices go out automatically {{ implode(' and ', config('membership.reminders.before_days')) }} days before</span>
        </header>
        <div class="overflow-x-auto">
            <table class="ledger">
                <thead><tr><th>Member</th><th>Coverage ends</th><th>Next due</th><th class="num">Monthly</th></tr></thead>
                <tbody>
                    @forelse ($this->dueSoonMembers as $member)
                        <tr>
                            <td>
                                <div class="font-medium">{{ $member->company_name }}</div>
                                <div class="text-[.8125rem] text-[var(--color-ink-2)]">{{ $member->email }}</div>
                            </td>
                            <td>{{ $member->paid_through->format('j M Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($member->dueOn())->format('j M Y') }}</td>
                            <td class="num">{{ config('membership.currency_symbol') }}{{ number_format($member->effectiveMonthlyFee(), 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty"><strong>Nothing due this week.</strong></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <header>
            <h2 class="text-[1.0625rem] font-semibold">Recently recorded payments</h2>
            <span class="hint">
                Last 30 days: {{ $this->emailActivity['reminders_30d'] }} reminders,
                {{ $this->emailActivity['announcements_30d'] }} announcements
                @if ($this->emailActivity['failed_7d']), {{ $this->emailActivity['failed_7d'] }} failed this week @endif
            </span>
        </header>
        <div class="overflow-x-auto">
            <table class="ledger">
                <thead><tr><th>Member</th><th>Received</th><th>Covers</th><th>Method</th><th class="num">Amount</th></tr></thead>
                <tbody>
                    @forelse ($this->recentPayments as $payment)
                        <tr>
                            <td class="font-medium">{{ $payment->member->company_name }}</td>
                            <td>{{ $payment->paid_on->format('j M Y') }}</td>
                            <td>{{ $payment->period_start->format('j M') }} – {{ $payment->period_end->format('j M Y') }}</td>
                            <td>{{ $payment->methodLabel() }}</td>
                            <td class="num">{{ config('membership.currency_symbol') }}{{ number_format($payment->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty"><strong>No payments recorded yet.</strong>Open a member and use Record payment.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
