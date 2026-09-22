<div>
    <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold tracking-tight">Member types</h1>
        <button wire:click="startEdit" class="btn">Add member type</button>
    </header>

    <section class="panel">
        <header>
            <h2 class="text-[1.0625rem] font-semibold">{{ $memberTypes->count() }} {{ Str::plural('type', $memberTypes->count()) }}</h2>
            <span class="hint">Tiers and their standard monthly fee</span>
        </header>
        <div class="overflow-x-auto">
            <table class="ledger">
                <thead><tr><th>Name</th><th>Description</th><th class="num">Monthly fee</th><th class="num">Members</th><th></th></tr></thead>
                <tbody>
                    @forelse ($memberTypes as $memberType)
                        <tr>
                            <td class="font-medium">{{ $memberType->name }}</td>
                            <td>{{ Str::limit($memberType->description, 80) ?: '—' }}</td>
                            <td class="num">{{ config('membership.currency_symbol') }}{{ number_format($memberType->monthly_fee, 2) }}</td>
                            <td class="num">{{ $memberType->members_count }}</td>
                            <td class="whitespace-nowrap">
                                <button wire:click="startEdit({{ $memberType->id }})" class="btn btn-quiet btn-sm">Edit</button>
                                <button wire:click="delete({{ $memberType->id }})"
                                        wire:confirm="Delete {{ $memberType->name }}?"
                                        class="btn btn-quiet btn-sm">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty"><strong>No member types yet.</strong>Add one to start assigning tiers.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($form !== [])
        <div class="scrim" wire:click.self="cancel">
            <div class="sheet sheet-narrow" role="dialog" aria-modal="true">
                <header>
                    <h2 class="text-[1.0625rem] font-semibold">{{ $editingId ? 'Edit member type' : 'Add member type' }}</h2>
                    <button wire:click="cancel" class="btn btn-quiet btn-sm">Close</button>
                </header>
                <form wire:submit="save" class="p-[1.1rem]">
                    <div class="field">
                        <label for="mt-name">Name</label>
                        <input id="mt-name" wire:model="form.name">
                        @error('form.name') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="mt-description">Description</label>
                        <textarea id="mt-description" rows="2" wire:model="form.description"></textarea>
                        @error('form.description') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <div class="field">
                            <label for="mt-fee">Monthly fee</label>
                            <input id="mt-fee" type="number" step="0.01" min="0" wire:model="form.monthly_fee">
                            <div class="note">What a company on this tier pays each month, unless overridden.</div>
                            @error('form.monthly_fee') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <div class="field">
                            <label for="mt-sort">Sort order</label>
                            <input id="mt-sort" type="number" wire:model="form.sort_order">
                            <div class="note">Lower numbers list first.</div>
                            @error('form.sort_order') <div class="error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="btn">{{ $editingId ? 'Save changes' : 'Add member type' }}</button>
                        <button type="button" wire:click="cancel" class="btn btn-quiet">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
