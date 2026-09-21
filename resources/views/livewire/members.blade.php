<div>
    <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold tracking-tight">Members</h1>
        <button wire:click="startEdit" class="btn">Add member</button>
    </header>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <div class="max-w-sm flex-1 basis-56">
            <label class="sr-only" for="search">Search members</label>
            <input id="search" type="search" wire:model.live.debounce.300ms="search"
                   placeholder="Search company name, email or phone"
                   class="w-full border border-[var(--color-rule)] bg-white px-2.5 py-2 text-[.9375rem]">
        </div>
        @foreach (['all' => 'Everyone', 'overdue' => 'Overdue', 'due_soon' => 'Due soon', 'current' => 'Paid up', 'lapsed' => 'Lapsed', 'cancelled' => 'Cancelled'] as $key => $label)
            <button wire:click="setFilter('{{ $key }}')" class="chip" aria-pressed="{{ $filter === $key ? 'true' : 'false' }}">{{ $label }}</button>
        @endforeach
    </div>

    <section class="panel">
        <header>
            <h2 class="text-[1.0625rem] font-semibold">{{ $members->total() }} {{ Str::plural('member', $members->total()) }}</h2>
        </header>
        <div class="overflow-x-auto">
            <table class="ledger">
                <thead>
                    <tr><th>Member</th><th>Type</th><th>Standing</th><th>Paid through</th><th class="num">Monthly</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td>
                                <button wire:click="view({{ $member->id }})"
                                        class="font-medium underline decoration-[var(--color-rule)] hover:decoration-[var(--color-ink)]">{{ $member->company_name }}</button>
                                <div class="text-[.8125rem] text-[var(--color-ink-2)]">
                                    {{ $member->email }}@if ($member->unsubscribed_at) · no announcements @endif
                                </div>
                            </td>
                            <td>{{ $member->memberType?->name ?? '—' }}</td>
                            <td>
                                <span class="mark mark-{{ $member->billingState() }}">{{ $member->billingLabel() }}</span>
                                @if ($member->billingState() === 'overdue')
                                    <span class="late-days">{{ $member->daysOverdue() }}d</span>
                                @endif
                            </td>
                            <td>{{ $member->paid_through?->format('j M Y') ?? '—' }}</td>
                            <td class="num">{{ config('membership.currency_symbol') }}{{ number_format($member->effectiveMonthlyFee(), 2) }}</td>
                            <td class="whitespace-nowrap">
                                <button wire:click="startPayment({{ $member->id }})" class="btn btn-quiet btn-sm">Record payment</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty"><strong>No members match.</strong>{{ $search ? 'Try a different search.' : 'Add your first member to get started.' }}</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div>{{ $members->links() }}</div>

    {{-- ---------------------------------------------------------- payment --}}
    @if ($payingId && $periodPreview)
        <div class="scrim" wire:click.self="closeAll">
            <div class="sheet sheet-narrow" role="dialog" aria-modal="true" aria-label="Record payment">
                <header>
                    <h2 class="text-[1.0625rem] font-semibold">Record payment</h2>
                    <button wire:click="closeAll" class="btn btn-quiet btn-sm">Close</button>
                </header>
                <form wire:submit="savePayment" class="p-[1.1rem]">
                    <div class="notice">
                        This payment covers
                        <strong>{{ \Carbon\Carbon::parse($periodPreview['period_start'])->format('j M Y') }}
                        – {{ \Carbon\Carbon::parse($periodPreview['period_end'])->format('j M Y') }}</strong>.
                        <div class="mt-1.5 text-[.8125rem] text-[var(--color-ink-2)]">
                            Coverage continues from where it ended, so no time is lost even if the payment is late.
                        </div>
                    </div>

                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <div class="field">
                            <label for="months">Months paid for</label>
                            <select id="months" wire:model.live="months">
                                @foreach ([1, 2, 3, 6, 12] as $n)
                                    <option value="{{ $n }}">{{ $n }} {{ Str::plural('month', $n) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="amount">Amount received</label>
                            <input id="amount" type="number" step="0.01" min="0" wire:model="amount">
                            @error('amount') <div class="error">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <div class="field">
                            <label for="paidOn">Date received</label>
                            <input id="paidOn" type="date" wire:model="paidOn">
                            <div class="note">The date the money arrived, not today's date.</div>
                            @error('paidOn') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label for="method">Method</label>
                            <select id="method" wire:model="method">
                                @foreach (\App\Models\Payment::METHODS as $m)
                                    <option value="{{ $m }}">{{ ucfirst(str_replace('_', ' ', $m)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="field">
                        <label for="reference">Reference</label>
                        <input id="reference" wire:model="reference" placeholder="Transfer reference or receipt number">
                    </div>

                    <div class="field">
                        <label for="note">Note</label>
                        <input id="note" wire:model="note" placeholder="Optional">
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="btn" wire:loading.attr="disabled">Save payment</button>
                        <button type="button" wire:click="closeAll" class="btn btn-quiet">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ------------------------------------------------------ member form --}}
    @if ($form !== [])
        <div class="scrim" wire:click.self="closeAll">
            <div class="sheet sheet-narrow" role="dialog" aria-modal="true">
                <header>
                    <h2 class="text-[1.0625rem] font-semibold">{{ $editingId ? 'Edit member' : 'Add member' }}</h2>
                    <button wire:click="closeAll" class="btn btn-quiet btn-sm">Close</button>
                </header>
                <form wire:submit="saveMember" class="p-[1.1rem]">
                    <div class="field">
                        <label for="f-company">Company name</label>
                        <input id="f-company" wire:model="form.company_name">
                        @error('form.company_name') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <div class="field">
                            <label for="f-business-type">Business type</label>
                            <select id="f-business-type" wire:model="form.business_type_id">
                                <option value="">—</option>
                                @foreach ($businessTypes as $businessType)
                                    <option value="{{ $businessType->id }}">{{ $businessType->name }}</option>
                                @endforeach
                            </select>
                            @error('form.business_type_id') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label for="f-type">Member type</label>
                            <select id="f-type" wire:model.live="form.member_type_id">
                                <option value="">—</option>
                                @foreach ($memberTypes as $memberType)
                                    <option value="{{ $memberType->id }}">{{ $memberType->name }}</option>
                                @endforeach
                            </select>
                            @error('form.member_type_id') <div class="error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="field">
                        <label for="f-email">Email</label>
                        <input id="f-email" type="email" wire:model="form.email">
                        <div class="note">Used for announcements and payment reminders.</div>
                        @error('form.email') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="f-phone">Phone</label>
                        <input id="f-phone" wire:model="form.phone">
                    </div>
                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <div class="field">
                            <label for="f-contact-person">Contact person</label>
                            <input id="f-contact-person" wire:model="form.contact_person">
                            @error('form.contact_person') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label for="f-contact-position">Contact person's position</label>
                            <input id="f-contact-position" wire:model="form.contact_person_position">
                            @error('form.contact_person_position') <div class="error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <div class="field">
                            <label for="f-fee">Monthly fee override</label>
                            <input id="f-fee" type="number" step="0.01" min="0" wire:model="form.monthly_fee"
                                   placeholder="{{ $memberTypes->firstWhere('id', (int) ($form['member_type_id'] ?: 0))?->monthly_fee ?? '0.00' }}">
                            <div class="note">Leave blank to use the member type's standard fee.</div>
                            @error('form.monthly_fee') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label for="f-join">Join date</label>
                            <input id="f-join" type="date" wire:model="form.join_date">
                            <div class="note">The first unpaid period starts here.</div>
                            @error('form.join_date') <div class="error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="field">
                        <label for="f-notes">Notes</label>
                        <textarea id="f-notes" rows="3" wire:model="form.notes"></textarea>
                    </div>
                    <div class="field">
                        <label class="flex items-start gap-2 text-sm font-normal">
                            <input type="checkbox" wire:model="form.marketing_opt_in" class="mt-1 w-auto">
                            <span>Send announcements about events and activities. Payment reminders are sent either way.</span>
                        </label>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="btn">{{ $editingId ? 'Save changes' : 'Add member' }}</button>
                        <button type="button" wire:click="closeAll" class="btn btn-quiet">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ----------------------------------------------------------- detail --}}
    @if ($detail)
        <div class="scrim" wire:click.self="closeAll">
            <div class="sheet" role="dialog" aria-modal="true" aria-label="{{ $detail->company_name }}">
                <header>
                    <div>
                        <h2 class="text-[1.0625rem] font-semibold">{{ $detail->company_name }}</h2>
                        <div class="text-[.8125rem] text-[var(--color-ink-2)]">
                            {{ $detail->email }}@if ($detail->phone) · {{ $detail->phone }} @endif
                        </div>
                    </div>
                    <button wire:click="closeAll" class="btn btn-quiet btn-sm">Close</button>
                </header>

                <div class="p-[1.1rem]">
                    <dl class="mb-6 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 text-sm">
                        <dt class="text-[var(--color-ink-2)]">Standing</dt>
                        <dd class="font-medium">
                            <span class="mark mark-{{ $detail->billingState() }}">{{ $detail->billingLabel() }}</span>
                            @if ($detail->billingState() === 'overdue')
                                <span class="late-days">{{ $detail->daysOverdue() }} days late</span>
                            @endif
                        </dd>
                        <dt class="text-[var(--color-ink-2)]">Paid through</dt>
                        <dd class="font-medium">{{ $detail->paid_through?->format('j M Y') ?? '—' }}</dd>
                        <dt class="text-[var(--color-ink-2)]">Next due</dt>
                        <dd class="font-medium">{{ \Carbon\Carbon::parse($detail->dueOn())->format('j M Y') }}</dd>
                        <dt class="text-[var(--color-ink-2)]">Member type</dt>
                        <dd class="font-medium">{{ $detail->memberType?->name ?? '—' }}</dd>
                        <dt class="text-[var(--color-ink-2)]">Monthly fee</dt>
                        <dd class="font-medium">{{ config('membership.currency_symbol') }}{{ number_format($detail->effectiveMonthlyFee(), 2) }}</dd>
                        <dt class="text-[var(--color-ink-2)]">Joined</dt>
                        <dd class="font-medium">{{ $detail->join_date->format('j M Y') }}</dd>
                        <dt class="text-[var(--color-ink-2)]">Announcements</dt>
                        <dd class="font-medium">{{ $detail->unsubscribed_at ? 'Unsubscribed' : ($detail->marketing_opt_in ? 'Subscribed' : 'Opted out') }}</dd>
                    </dl>

                    <section class="panel !mb-6">
                        <header><h3 class="text-[.9375rem] font-semibold">Payments</h3>
                            <span class="hint">{{ $detail->payments->count() }} recorded</span></header>
                        <div class="overflow-x-auto">
                            <table class="ledger">
                                <thead><tr><th>Received</th><th>Covers</th><th>Method</th><th class="num">Amount</th><th></th></tr></thead>
                                <tbody>
                                    @forelse ($detail->payments->sortByDesc('period_end') as $payment)
                                        <tr>
                                            <td>{{ $payment->paid_on->format('j M Y') }}</td>
                                            <td>
                                                {{ $payment->period_start->format('j M') }} – {{ $payment->period_end->format('j M Y') }}
                                                @if ($payment->reference)
                                                    <div class="text-[.8125rem] text-[var(--color-ink-2)]">{{ $payment->reference }}</div>
                                                @endif
                                            </td>
                                            <td>{{ $payment->methodLabel() }}</td>
                                            <td class="num">{{ config('membership.currency_symbol') }}{{ number_format($payment->amount, 2) }}</td>
                                            <td>
                                                <button wire:click="deletePayment({{ $payment->id }})"
                                                        wire:confirm="Remove this payment? The member's paid-through date will move back."
                                                        class="text-[.8125rem] text-[var(--color-ink-2)] underline hover:text-[var(--color-ink)]">Remove</button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5"><div class="empty"><strong>No payments yet.</strong></div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="panel !mb-0">
                        <header><h3 class="text-[.9375rem] font-semibold">Email history</h3></header>
                        <div class="overflow-x-auto">
                            <table class="ledger">
                                <thead><tr><th>Sent</th><th>Subject</th><th>Kind</th><th>Status</th></tr></thead>
                                <tbody>
                                    @forelse ($detail->emails as $email)
                                        <tr>
                                            <td>{{ ($email->sent_at ?? $email->created_at)->format('j M Y') }}</td>
                                            <td>{{ $email->subject }}</td>
                                            <td>{{ $email->kindLabel() }}</td>
                                            <td>
                                                {{ $email->status }}
                                                @if ($email->error)
                                                    <div class="text-[.8125rem] text-[var(--color-ink-2)]">{{ $email->error }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4"><div class="empty"><strong>No email sent to this member yet.</strong></div></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div class="flex flex-wrap gap-2 border-t border-[var(--color-rule)] bg-[var(--color-wash)] p-[1.1rem]">
                    <button wire:click="startPayment({{ $detail->id }})" class="btn">Record payment</button>
                    <button wire:click="startEdit({{ $detail->id }})" class="btn btn-quiet">Edit details</button>
                    @if ($detail->status !== 'cancelled')
                        <a href="{{ route('directory.show', $detail) }}" target="_blank" rel="noopener" class="btn btn-quiet">View public profile</a>
                    @endif
                    @if ($detail->status === 'cancelled')
                        <button wire:click="reinstate({{ $detail->id }})" class="btn btn-quiet">Reinstate</button>
                    @else
                        <button wire:click="cancelMembership({{ $detail->id }})"
                                wire:confirm="Cancel this membership? Payment history is kept and it can be reinstated later."
                                class="btn btn-danger">Cancel membership</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
