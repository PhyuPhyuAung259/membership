<div>
    @include('partials.flash')
    <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold tracking-tight">Roles &amp; permissions</h1>
        <button wire:click="startAdd" class="btn">Add role</button>
    </header>

    <section class="panel">
        <header>
            <h2 class="text-[1.0625rem] font-semibold">{{ $roles->count() }} {{ Str::plural('role', $roles->count()) }}</h2>
        </header>
        <div class="overflow-x-auto">
            <table class="ledger">
                <thead><tr><th>Role</th><th>Permissions</th><th class="num">Staff</th><th></th></tr></thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td class="font-medium">
                                {{ $role->name }}
                                @if ($role->name === \App\Livewire\Roles::PROTECTED_ROLE)
                                    <span class="hint">always has every permission</span>
                                @endif
                            </td>
                            <td class="text-[.8125rem] text-[var(--color-ink-2)]">
                                @if ($role->name === \App\Livewire\Roles::PROTECTED_ROLE)
                                    Everything
                                @else
                                    {{ $role->permissions->pluck('name')->implode(', ') ?: '—' }}
                                @endif
                            </td>
                            <td class="num">{{ $role->users_count }}</td>
                            <td class="whitespace-nowrap">
                                @if ($role->name !== \App\Livewire\Roles::PROTECTED_ROLE)
                                    <button wire:click="startEdit({{ $role->id }})" class="btn btn-quiet btn-sm">Edit</button>
                                    <button wire:click="delete({{ $role->id }})"
                                            wire:confirm="Delete the {{ $role->name }} role?"
                                            class="btn btn-quiet btn-sm">Delete</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if ($formOpen)
        <div class="scrim" wire:click.self="closeForm">
            <div class="sheet sheet-narrow" role="dialog" aria-modal="true">
                <header>
                    <h2 class="text-[1.0625rem] font-semibold">{{ $editingId ? 'Edit role' : 'Add role' }}</h2>
                    <button wire:click="closeForm" class="btn btn-quiet btn-sm">Close</button>
                </header>
                <form wire:submit="save" class="p-[1.1rem]">
                    <div class="field">
                        <label for="r-name">Role name</label>
                        <input id="r-name" wire:model="name">
                        @error('name') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="field">
                        <label>Permissions</label>
                        <div class="mt-1 space-y-2">
                            @foreach ($permissionList as $key => $description)
                                <label class="flex items-start gap-2 text-sm font-normal">
                                    <input type="checkbox" value="{{ $key }}" wire:model="permissions" class="mt-1 w-auto">
                                    <span>
                                        <span class="font-medium text-[var(--color-ink)]">{{ $key }}</span>
                                        <span class="block text-[.8125rem] text-[var(--color-ink-2)]">{{ $description }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('permissions') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="btn">{{ $editingId ? 'Save changes' : 'Add role' }}</button>
                        <button type="button" wire:click="closeForm" class="btn btn-quiet">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
