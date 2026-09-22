<?php

namespace App\Livewire;

use App\Models\MemberType;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MemberTypes extends Component
{
    public ?int $editingId = null;
    public array $form = [];

    public function getMemberTypesProperty()
    {
        return MemberType::withCount('members')->ranked()->get();
    }

    public function startEdit(?int $id = null): void
    {
        $this->editingId = $id;
        $type = $id ? MemberType::findOrFail($id) : null;

        $this->form = [
            'name' => $type->name ?? '',
            'description' => $type->description ?? '',
            'monthly_fee' => $type ? (string) $type->monthly_fee : '0',
            'sort_order' => $type->sort_order ?? 0,
        ];
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.name' => 'required|string|max:100',
            'form.description' => 'nullable|string|max:1000',
            'form.monthly_fee' => 'required|numeric|min:0',
            'form.sort_order' => 'nullable|integer',
        ])['form'];

        $data['sort_order'] = $data['sort_order'] ?: 0;

        if ($this->editingId) {
            MemberType::findOrFail($this->editingId)->update($data);
        } else {
            MemberType::create($data);
        }

        $this->editingId = null;
        $this->form = [];
        session()->flash('status', 'Member type saved.');
    }

    public function cancel(): void
    {
        $this->editingId = null;
        $this->form = [];
    }

    /**
     * A tier that companies are on cannot be deleted — the database
     * restricts it. Reassign those companies to another tier first.
     */
    public function delete(int $id): void
    {
        $type = MemberType::findOrFail($id);

        if ($type->inUse()) {
            session()->flash('status', "Can't delete {$type->name} — companies are still on it.");

            return;
        }

        $type->delete();
        session()->flash('status', 'Member type deleted.');
    }

    public function render()
    {
        return view('livewire.member-types', ['memberTypes' => $this->memberTypes]);
    }
}
