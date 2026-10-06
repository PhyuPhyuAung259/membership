<?php

namespace App\Livewire;

use App\Support\Permissions;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Lets an admin create roles and choose what each one can do. Gated by the
 * 'manage-roles' permission.
 *
 * The permission keys themselves are fixed (see App\Support\Permissions) —
 * each one is wired to a real abort_unless()/@can() check elsewhere in the
 * code, so this page can only assign from that fixed list, never invent a
 * new key that nothing checks.
 *
 * 'Admin' is seeded and protected here (can't be renamed, deleted, or have
 * its permissions edited) because its real power comes from
 * AppServiceProvider's Gate::before bypass on the role name 'Admin', not
 * from the permission rows attached to it — so editing those rows would be
 * pure theatre while looking like it does something.
 */
#[Layout('layouts.app')]
class Roles extends Component
{
    public const PROTECTED_ROLE = 'Admin';

    public bool $formOpen = false;
    public ?int $editingId = null;
    public string $name = '';
    public array $permissions = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('manage-roles'), 403);
    }

    public function getRolesProperty()
    {
        return Role::with('permissions')->withCount('users')->orderBy('name')->get();
    }

    public function getPermissionListProperty(): array
    {
        return Permissions::ALL;
    }

    public function startAdd(): void
    {
        $this->formOpen = true;
        $this->editingId = null;
        $this->name = '';
        $this->permissions = [];
    }

    public function startEdit(int $id): void
    {
        $role = Role::findOrFail($id);
        abort_if($role->name === self::PROTECTED_ROLE, 403, 'The Admin role always has every permission.');

        $this->formOpen = true;
        $this->editingId = $id;
        $this->name = $role->name;
        $this->permissions = $role->permissions->pluck('name')->all();
    }

    public function closeForm(): void
    {
        $this->formOpen = false;
        $this->editingId = null;
        $this->name = '';
        $this->permissions = [];
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('roles', 'name')->ignore($this->editingId),
            ],
            'permissions' => 'array',
            'permissions.*' => 'in:' . implode(',', array_keys(Permissions::ALL)),
        ]);

        if ($this->editingId) {
            $role = Role::findOrFail($this->editingId);
            abort_if($role->name === self::PROTECTED_ROLE, 403);
            $role->update(['name' => $data['name']]);
        } else {
            $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        }

        $role->syncPermissions($data['permissions']);

        $this->closeForm();
        session()->flash('status', 'Role saved.');
    }

    public function delete(int $id): void
    {
        $role = Role::withCount('users')->findOrFail($id);

        abort_if($role->name === self::PROTECTED_ROLE, 403);

        if ($role->users_count > 0) {
            session()->flash('status', "Can't delete {$role->name} — staff are still assigned to it.");

            return;
        }

        $role->delete();
        session()->flash('status', 'Role deleted.');
    }

    public function render()
    {
        return view('livewire.roles', ['roles' => $this->roles, 'permissionList' => $this->permissionList]);
    }
}
