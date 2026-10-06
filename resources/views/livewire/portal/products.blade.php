<div>
    @include('partials.flash')
    <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Products &amp; brochures</h1>
            <p class="mt-1 text-[.8125rem] text-[var(--color-ink-2)]">Shown on your public directory page.</p>
        </div>
        @if (! $formOpen)
            <button wire:click="startAdd" class="btn">Add product</button>
        @endif
    </header>

    <section class="panel">
        <div class="overflow-x-auto">
            <table class="ledger">
                <thead><tr><th>Name</th><th>File</th><th>Description</th><th></th></tr></thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td class="font-medium">{{ $product->product_name }}</td>
                            <td>
                                @if ($product->fileUrl())
                                    <a href="{{ $product->fileUrl() }}" target="_blank" rel="noopener">{{ $product->isImage() ? 'Image' : 'Document' }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-[.8125rem] text-[var(--color-ink-2)]">{{ Str::limit($product->description, 60) }}</td>
                            <td class="whitespace-nowrap">
                                <button wire:click="startEdit({{ $product->id }})" class="btn btn-quiet btn-sm">Edit</button>
                                <button wire:click="delete({{ $product->id }})"
                                        wire:confirm="Remove this product?"
                                        class="btn btn-quiet btn-sm">Remove</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty"><strong>Nothing listed yet.</strong>Add a product to show it on your public profile.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($formOpen)
        <div class="scrim" wire:click.self="closeForm">
            <div class="sheet sheet-narrow" role="dialog" aria-modal="true">
                <header>
                    <h2 class="text-[1.0625rem] font-semibold">{{ $editingId ? 'Edit product' : 'Add product' }}</h2>
                    <button wire:click="closeForm" class="btn btn-quiet btn-sm">Close</button>
                </header>
                <form wire:submit="save" class="p-[1.1rem]">
                    <div class="field">
                        <label for="pp-name">Product name</label>
                        <input id="pp-name" wire:model="form.product_name">
                        @error('form.product_name') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="pp-desc">Description</label>
                        <textarea id="pp-desc" rows="2" wire:model="form.description"></textarea>
                        @error('form.description') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="pp-file">Image or PDF</label>
                        @if ($existingFilePath && ! $file)
                            <div class="mb-1.5 text-[.8125rem] text-[var(--color-ink-2)]">
                                Current file kept. <button type="button" wire:click="removeFile" class="underline">Remove it</button>
                            </div>
                        @endif
                        <input id="pp-file" type="file" wire:model="file" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf">
                        <div wire:loading wire:target="file" class="note">Uploading…</div>
                        @error('file') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="btn" wire:loading.attr="disabled">Save product</button>
                        <button type="button" wire:click="closeForm" class="btn btn-quiet">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
