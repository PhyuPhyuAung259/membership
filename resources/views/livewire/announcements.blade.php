<div>
    <header class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">Email</h1>
    </header>

    <section class="panel">
        <header>
            <h2 class="text-[1.0625rem] font-semibold">Write an announcement</h2>
            <span class="hint">{{ $recipientCount }} {{ Str::plural('member', $recipientCount) }} will receive this</span>
        </header>
        <form wire:submit="send" class="p-[1.1rem]">
            <div class="grid gap-x-4 sm:grid-cols-2">
                <div class="field">
                    <label for="audience">Send to</label>
                    <select id="audience" wire:model.live="audience">
                        @foreach (\App\Models\Broadcast::AUDIENCES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="note">
                        {{ $recipientCount }} {{ Str::plural('recipient', $recipientCount) }}@if ($excludedCount), {{ $excludedCount }} excluded for opting out @endif
                    </div>
                    @error('audience') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label for="event">Attach event details</label>
                    <select id="event" wire:model.live="eventId">
                        <option value="">None</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->id }}">{{ $event->title }}</option>
                        @endforeach
                    </select>
                    <div class="note">Adds the date, time and place to the email.</div>
                </div>
            </div>

            <div class="field">
                <label for="subject">Subject</label>
                <input id="subject" wire:model="subject">
                @error('subject') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="body">Message</label>
                <textarea id="body" rows="9" wire:model="body"></textarea>
                <div class="note">Plain text. Leave a blank line between paragraphs. An unsubscribe link is added automatically.</div>
                @error('body') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="sendTest" class="btn btn-quiet" wire:loading.attr="disabled">
                    Send a test to me
                </button>
                <button type="submit" class="btn" wire:loading.attr="disabled"
                        wire:confirm="Send to {{ $recipientCount }} members? This cannot be undone.">
                    Send to members
                </button>
            </div>
        </form>
    </section>

    <section class="panel">
        <header><h2 class="text-[1.0625rem] font-semibold">Sent announcements</h2></header>
        <div class="overflow-x-auto">
            <table class="ledger">
                <thead><tr><th>Subject</th><th>Sent</th><th>Audience</th><th>By</th><th class="num">Delivered</th></tr></thead>
                <tbody>
                    @forelse ($broadcasts as $broadcast)
                        <tr>
                            <td>
                                <div class="font-medium">{{ $broadcast->subject }}</div>
                                @if ($broadcast->event)
                                    <div class="text-[.8125rem] text-[var(--color-ink-2)]">{{ $broadcast->event->title }}</div>
                                @endif
                            </td>
                            <td>{{ $broadcast->sent_at?->format('j M Y') ?? 'Draft' }}</td>
                            <td>{{ \App\Models\Broadcast::AUDIENCES[$broadcast->audience] ?? $broadcast->audience }}</td>
                            <td>{{ $broadcast->sentBy->name ?? '—' }}</td>
                            <td class="num">
                                {{ $broadcast->sent_count }} / {{ $broadcast->queued_count }}
                                @if ($broadcast->failed_count)
                                    <span class="late-days">({{ $broadcast->failed_count }} failed)</span>
                                @endif
                                @unless ($broadcast->isFinished())
                                    <div class="text-[.8125rem] text-[var(--color-ink-2)]">sending…</div>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty"><strong>Nothing sent yet.</strong></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
