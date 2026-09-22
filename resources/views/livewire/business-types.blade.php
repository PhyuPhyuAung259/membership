<div>
    <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold tracking-tight">Business types</h1>
    </header>

    <section class="panel max-w-xl">
        <header>
            <h2 class="text-[1.0625rem] font-semibold">{{ $businessTypes->count() }} {{ Str::plural('type', $businessTypes->count()) }}</h2>
            <span class="hint">The industries members can be tagged with</span>
        </header>

        <div class="overflow-x-auto">
            <table class="ledger">
                <thead><tr><th>Name</th><th></th></tr></thead>
                <tbody>
                    @forelse ($businessTypes as $businessType)
                        <tr>
                            @if ($editingId === $businessType->id)
                                <td colspan="2">
                                    <form wire:submit="rename" class="flex flex-wrap items-center gap-2">
                                        <input wire:model="editingName" autofocus class="max-w-xs">
                                        <button type="submit" class="btn btn-sm">Save</button>
                                        <button type="button" wire:click="cancelRename" class="btn btn-quiet btn-sm">Cancel</button>
                                    </form>
                                    @error('editingName') <div class="error">{{ $message }}</div> @enderror
                                </td>
                            @else
                                <td>{{ $businessType->name }}</td>
                                <td class="whitespace-nowrap">
                                    <button wire:click="startRename({{ $businessType->id }})" class="btn btn-quiet btn-sm">Rename</button>
                                    <button wire:click="delete({{ $businessType->id }})"
                                            wire:confirm="Delete {{ $businessType->name }}?"
                                            class="btn btn-quiet btn-sm">Delete</button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="2"><div class="empty"><strong>No business types yet.</strong>Add one below.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form wire:submit="add" class="flex flex-wrap items-end gap-2 border-t border-[var(--color-rule)] p-[1.1rem]">
            <div class="field !mb-0 flex-1">
                <label for="new-type">Add a business type</label>
                <input id="new-type" wire:model="name" placeholder="e.g. Hospitality">
                @error('name') <div class="error">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn">Add</button>
        </form>
    </section>
</div>
