<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;

/**
 * Staff accounts and which role each one holds. Gated by the 'manage-staff'
 * permission — both by route middleware and here, same belt-and-braces
 * pattern as the rest of this app.
 *
 * Each account holds exactly one role at a time (assigned via syncRoles),
 * even though spatie's model supports more — a staff member having two
 * roles at once isn't a scenario this app needs, and one dropdown per row
 * is a lot simpler than a multi-select.
 */
#[Layout('layouts.app')]
class Users extends Component
{
    public bool $formOpen = false;
    public array $form = [];

    // Shown once, right after creating a staff account, so it can be
    // handed to them — nothing emails it and nothing stores it in the
    // clear, so this is the only chance to pass it along.
    public ?string $generatedPassword = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('manage-staff'), 403);
    }

    public function getUsersProperty()
    {
        return User::with('roles')->orderBy('name')->get();
    }

    public function getRoleNamesProperty()
    {
        return Role::orderBy('name')->pluck('name');
    }

    public function startAdd(): void
    {
        $this->formOpen = true;
        $this->form = ['name' => '', 'email' => '', 'role' => $this->roleNames->contains('Staff') ? 'Staff' : ($this->roleNames->first() ?? '')];
        $this->generatedPassword = null;
    }

    public function closeForm(): void
    {
        $this->formOpen = false;
        $this->form = [];
        $this->generatedPassword = null;
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.name' => 'required|string|max:255',
            'form.email' => 'required|email|max:255|unique:users,email',
            'form.role' => 'required|in:' . $this->roleNames->implode(','),
        ])['form'];

        $password = Str::password(16);

        $user = User::create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($data['role']);

        $this->formOpen = false;
        $this->form = [];
        $this->generatedPassword = $password;
        session()->flash('status', 'Staff account created.');
    }

    /**
     * An admin can't change their own role or delete themself. Only holders
     * of 'manage-staff' reach this component at all, and an Admin's power
     * comes from AppServiceProvider's Gate::before bypass on the role name
     * 'Admin' — not from this page — so blocking those two self-actions is
     * enough on its own to guarantee whoever is signed in and acting keeps
     * their own access throughout.
     */
    public function setRole(int $id, string $role): void
    {
        if (! $this->roleNames->contains($role)) {
            return;
        }

        if ($id === auth()->id()) {
            session()->flash('status', "You can't change your own role.");

            return;
        }

        User::findOrFail($id)->syncRoles([$role]);
        session()->flash('status', 'Role updated.');
    }

    public function delete(int $id): void
    {
        if ($id === auth()->id()) {
            session()->flash('status', "You can't delete your own account.");

            return;
        }

        User::findOrFail($id)->delete();
        session()->flash('status', 'Staff account removed.');
    }

    public function render()
    {
        return view('livewire.users', ['users' => $this->users, 'roleNames' => $this->roleNames]);
    }
}
