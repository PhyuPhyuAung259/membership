<div>
    @include('partials.flash')
    <header class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">Documents</h1>
    </header>

    <section class="panel !mb-0">
        <header><h2 class="text-[1.0625rem] font-semibold">Business registration document</h2></header>
        <div class="p-[1.1rem]">
            @if ($member->registration_document_path)
                <div class="mb-4 text-sm">
                    <a href="{{ route('portal.documents.registration') }}" target="_blank" rel="noopener">View current document</a>
                    <span class="text-[var(--color-ink-2)]">
                        — uploaded {{ $member->registration_document_updated_at?->format('j M Y') ?? 'a while ago' }}
                    </span>
                </div>
            @else
                <div class="empty mb-4"><strong>Nothing on file yet.</strong>Upload your business registration document below.</div>
            @endif

            <form wire:submit="saveDocument">
                <div class="field">
                    <label for="p-doc">{{ $member->registration_document_path ? 'Replace document' : 'Upload document' }}</label>
                    <input id="p-doc" type="file" wire:model="registrationDocument" accept=".pdf,.jpg,.jpeg,.png">
                    <div class="note">PDF, JPG or PNG, up to 5MB.</div>
                    <div wire:loading wire:target="registrationDocument" class="note">Uploading…</div>
                    @error('registrationDocument') <div class="error">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn" wire:loading.attr="disabled">Save document</button>
            </form>
        </div>
    </section>
</div>
