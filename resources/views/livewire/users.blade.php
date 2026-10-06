<div>
    @include('partials.flash')
    <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold tracking-tight">Staff accounts</h1>
        <button wire:click="startAdd" class="btn">Add staff account</button>
    </header>

    @if ($generatedPassword)
        <div class="notice notice-good mb-6">
            Account created. Temporary password — share it with them now, this is the only time it's shown:
            <div class="mt-1 font-mono text-[.9375rem] font-semibold tracking-wide">{{ $generatedPassword }}</div>
            <div class="mt-1 text-[.8125rem]">They should sign in and set their own password from their profile page.</div>
        </div>
    @endif

    <section class="panel">
        <header>
            <h2 class="text-[1.0625rem] font-semibold">{{ $users->count() }} {{ Str::plural('account', $users->count()) }}</h2>
        </header>
        <div class="overflow-x-auto">
            <table class="ledger">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th></th></tr></thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="font-medium">{{ $user->name }}@if ($user->id === auth()->id()) <span class="hint">(you)</span> @endif</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if ($user->id === auth()->id())
                                    {{ $user->roles->pluck('name')->implode(', ') ?: '—' }}
                                @else
                                    <select wire:change="setRole({{ $user->id }}, $event.target.value)" class="w-auto py-1">
                                        @foreach ($roleNames as $role)
                                            <option value="{{ $role }}" @selected($user->roles->pluck('name')->contains($role))>{{ $role }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">
                                @if ($user->id !== auth()->id())
                                    <button wire:click="delete({{ $user->id }})"
                                            wire:confirm="Remove {{ $user->name }}'s account? They will no longer be able to sign in."
                                            class="btn btn-quiet btn-sm">Remove</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty"><strong>No staff accounts yet.</strong></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($formOpen)
        <div class="scrim" wire:click.self="closeForm">
            <div class="sheet sheet-narrow" role="dialog" aria-modal="true">
                <header>
                    <h2 class="text-[1.0625rem] font-semibold">Add staff account</h2>
                    <button wire:click="closeForm" class="btn btn-quiet btn-sm">Close</button>
                </header>
                <form wire:submit="save" class="p-[1.1rem]">
                    <div class="field">
                        <label for="u-name">Name</label>
                        <input id="u-name" wire:model="form.name">
                        @error('form.name') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="u-email">Email</label>
                        <input id="u-email" type="email" wire:model="form.email">
                        @error('form.email') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label for="u-role">Role</label>
                        <select id="u-role" wire:model="form.role">
                            @foreach ($roleNames as $role)
                                <option value="{{ $role }}">{{ $role }}</option>
                            @endforeach
                        </select>
                        <div class="note">What this role can do is set on the <a href="{{ route('roles') }}">Roles &amp; permissions</a> page.</div>
                        @error('form.role') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="btn">Create account</button>
                        <button type="button" wire:click="closeForm" class="btn btn-quiet">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
