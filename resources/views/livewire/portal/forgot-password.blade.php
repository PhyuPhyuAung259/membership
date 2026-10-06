<div>
    <h1 class="mb-4 text-[1.0625rem] font-semibold">Reset your password</h1>

    @if ($status)
        <div class="notice notice-good mb-4">{{ $status }}</div>
    @else
        <p class="mb-4 text-[.875rem] text-[var(--color-ink-2)]">
            Enter the email address on your membership and we'll send a link to set a new password.
        </p>
        <form wire:submit="sendLink">
            <div class="field">
                <label for="p-email">Email</label>
                <input id="p-email" type="email" wire:model="email" autofocus>
                @error('email') <div class="error">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn w-full" wire:loading.attr="disabled">Send reset link</button>
        </form>
    @endif

    <div class="mt-4 text-center text-[.8125rem]">
        <a href="{{ route('portal.login') }}">Back to sign in</a>
    </div>
</div>
