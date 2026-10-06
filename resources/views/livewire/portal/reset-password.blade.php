<div>
    <h1 class="mb-4 text-[1.0625rem] font-semibold">Set a new password</h1>

    <form wire:submit="resetPassword">
        <div class="field">
            <label for="p-email">Email</label>
            <input id="p-email" type="email" wire:model="email" autofocus>
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="p-password">New password</label>
            <input id="p-password" type="password" wire:model="password" autocomplete="new-password">
            @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="p-password-confirm">Confirm password</label>
            <input id="p-password-confirm" type="password" wire:model="password_confirmation" autocomplete="new-password">
        </div>
        <button type="submit" class="btn w-full" wire:loading.attr="disabled">Set password</button>
    </form>
</div>
