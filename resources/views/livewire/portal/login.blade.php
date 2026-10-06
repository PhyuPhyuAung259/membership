<div>
    <h1 class="mb-4 text-[1.0625rem] font-semibold">Sign in</h1>

    @if (session('status'))
        <div class="notice notice-good mb-4">{{ session('status') }}</div>
    @endif

    <form wire:submit="login">
        <div class="field">
            <label for="p-email">Email</label>
            <input id="p-email" type="email" wire:model="email" autofocus autocomplete="username">
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="p-password">Password</label>
            <input id="p-password" type="password" wire:model="password" autocomplete="current-password">
            @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label class="flex items-center gap-2 text-sm font-normal">
                <input type="checkbox" wire:model="remember" class="w-auto">
                <span>Remember me</span>
            </label>
        </div>
        <button type="submit" class="btn w-full" wire:loading.attr="disabled">Sign in</button>
    </form>

    <div class="mt-4 text-center text-[.8125rem]">
        <a href="{{ route('portal.password.request') }}">Forgot your password?</a>
    </div>
</div>
