<div>
    <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold tracking-tight">Events and activities</h1>
        <button wire:click="startEdit" class="btn">Add event</button>
    </header>

    <section class="panel">
        <header>
            <h2 class="text-[1.0625rem] font-semibold">{{ $events->count() }} {{ Str::plural('entry', $events->count()) }}</h2>
            <span class="hint">Announce an event to email it to your members</span>
        </header>
        <div class="overflow-x-auto">
            <table class="ledger">
                <thead><tr><th>Event</th><th>When</th><th>Where</th><th>Announced</th><th></th></tr></thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td>
                                <div class="font-medium">{{ $event->title }}</div>
                                @if ($event->body)
                                    <div class="text-[.8125rem] text-[var(--color-ink-2)]">{{ Str::limit($event->body, 110) }}</div>
                                @endif
                            </td>
                            <td>
                                {{ $event->event_date?->format('j M Y') ?? '—' }}
                                @if ($event->event_time)
                                    <div class="text-[.8125rem] text-[var(--color-ink-2)]">{{ $event->event_time }}</div>
                                @endif
                            </td>
                            <td>{{ $event->location ?? '—' }}</td>
                            <td>{{ $event->broadcasts_count ? $event->broadcasts_count . '×' : 'Not yet' }}</td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('announcements', ['eventId' => $event->id]) }}" class="btn btn-sm no-underline">Announce</a>
                                <button wire:click="startEdit({{ $event->id }})" class="btn btn-quiet btn-sm">Edit</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty"><strong>No events yet.</strong>Add one, then announce it to your members.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($form !== [])
        <div class="scrim" wire:click.self="closeForm">
            <div class="sheet sheet-narrow" role="dialog" aria-modal="true">
                <header>
                    <h2 class="text-[1.0625rem] font-semibold">{{ $editingId ? 'Edit event' : 'Add event' }}</h2>
                    <button wire:click="closeForm" class="btn btn-quiet btn-sm">Close</button>
                </header>
                <form wire:submit="save" class="p-[1.1rem]">
                    <div class="field">
                        <label for="e-title">Title</label>
                        <input id="e-title" wire:model="form.title">
                        @error('form.title') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <div class="field">
                            <label for="e-date">Date</label>
                            <input id="e-date" type="date" wire:model="form.event_date">
                        </div>
                        <div class="field">
                            <label for="e-time">Time</label>
                            <input id="e-time" wire:model="form.event_time" placeholder="6:30 PM">
                        </div>
                    </div>
                    <div class="field">
                        <label for="e-loc">Location</label>
                        <input id="e-loc" wire:model="form.location">
                    </div>
                    <div class="field">
                        <label for="e-body">Details</label>
                        <textarea id="e-body" rows="6" wire:model="form.body" placeholder="What members need to know."></textarea>
                        <div class="note">Leave a blank line between paragraphs.</div>
                    </div>
                    <div class="field">
                        <label for="e-image">Banner image</label>
                        <input id="e-image" type="file" wire:model="image" accept="image/*">
                        <div class="note">JPG, PNG, WebP or GIF, up to 2&nbsp;MB. Shown as the banner in the announcement email.</div>
                        @error('image') <div class="error">{{ $message }}</div> @enderror
                        <div wire:loading wire:target="image" class="note">Uploading…</div>
                        @if ($image)
                            <img src="{{ $image->temporaryUrl() }}" alt="Banner preview"
                                 class="mt-2 max-h-40 w-full rounded border border-[var(--color-rule)] object-cover">
                            <button type="button" wire:click="removeImage" class="btn btn-quiet btn-sm mt-1">Remove</button>
                        @elseif ($existingImagePath)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($existingImagePath) }}" alt="Banner preview"
                                 class="mt-2 max-h-40 w-full rounded border border-[var(--color-rule)] object-cover">
                            <button type="button" wire:click="removeImage" class="btn btn-quiet btn-sm mt-1">Remove image</button>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="btn">{{ $editingId ? 'Save changes' : 'Add event' }}</button>
                        @if ($editingId)
                            <button type="button" wire:click="delete({{ $editingId }})"
                                    wire:confirm="Delete this event? Announcements already sent are kept."
                                    class="btn btn-danger">Delete</button>
                        @endif
                        <button type="button" wire:click="closeForm" class="btn btn-quiet">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
