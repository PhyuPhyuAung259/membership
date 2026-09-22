<?php

namespace App\Livewire;

use App\Models\BusinessType;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BusinessTypes extends Component
{
    public string $name = '';
    public ?int $editingId = null;
    public string $editingName = '';

    public function getBusinessTypesProperty()
    {
        return BusinessType::alphabetical()->get();
    }

    public function add(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:100|unique:business_types,name',
        ]);

        BusinessType::create($data);

        $this->reset('name');
        session()->flash('status', 'Business type added.');
    }

    public function startRename(int $id): void
    {
        $type = BusinessType::findOrFail($id);
        $this->editingId = $id;
        $this->editingName = $type->name;
    }

    public function rename(): void
    {
        $data = $this->validate([
            'editingName' => "required|string|max:100|unique:business_types,name,{$this->editingId}",
        ]);

        BusinessType::findOrFail($this->editingId)->update(['name' => $data['editingName']]);

        $this->reset(['editingId', 'editingName']);
        session()->flash('status', 'Business type renamed.');
    }

    public function cancelRename(): void
    {
        $this->reset(['editingId', 'editingName']);
    }

    public function delete(int $id): void
    {
        $type = BusinessType::findOrFail($id);

        if ($type->inUse()) {
            session()->flash('status', "Can't delete {$type->name} — companies are still on it.");

            return;
        }

        $type->delete();
        session()->flash('status', 'Business type deleted.');
    }

    public function render()
    {
        return view('livewire.business-types', [
            'businessTypes' => $this->businessTypes,
        ]);
    }
}
