<?php

namespace App\Livewire\Portal;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.portal')]
class Products extends Component
{
    use WithFileUploads;

    public bool $formOpen = false;
    public ?int $editingId = null;
    public array $form = [];
    public $file = null;
    public ?string $existingFilePath = null;

    public function getProductsProperty()
    {
        return Auth::guard('member')->user()->products;
    }

    public function startAdd(): void
    {
        $this->formOpen = true;
        $this->editingId = null;
        $this->form = ['product_name' => '', 'description' => ''];
        $this->file = null;
        $this->existingFilePath = null;
    }

    /** Loads only from the signed-in member's own products — never trusts a bare id from the request. */
    public function startEdit(int $id): void
    {
        $product = $this->ownProductOrFail($id);

        $this->formOpen = true;
        $this->editingId = $id;
        $this->form = [
            'product_name' => $product->product_name,
            'description' => $product->description ?? '',
        ];
        $this->file = null;
        $this->existingFilePath = $product->file_path;
    }

    public function removeFile(): void
    {
        $this->file = null;
        $this->existingFilePath = null;
    }

    public function closeForm(): void
    {
        $this->formOpen = false;
        $this->editingId = null;
        $this->form = [];
        $this->file = null;
        $this->existingFilePath = null;
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.product_name' => 'required|string|max:200',
            'form.description' => 'nullable|string|max:1000',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,pdf|max:5120',
        ])['form'];

        if ($this->file) {
            $data['file_path'] = $this->file->store('products', 'public');
            $data['file_kind'] = Product::kindFor($this->file->getClientOriginalName());
            $data['file_original_name'] = $this->file->getClientOriginalName();
        } elseif ($this->editingId && $this->existingFilePath === null) {
            $data['file_path'] = null;
            $data['file_kind'] = null;
            $data['file_original_name'] = null;
        }

        $member = Auth::guard('member')->user();

        if ($this->editingId) {
            $this->ownProductOrFail($this->editingId)->update($data);
        } else {
            $data['member_id'] = $member->id;
            $data['sort_order'] = ($member->products()->max('sort_order') ?? 0) + 1;
            Product::create($data);
        }

        $this->closeForm();
        session()->flash('status', 'Product saved.');
    }

    public function delete(int $id): void
    {
        $this->ownProductOrFail($id)->delete();
        session()->flash('status', 'Product removed.');
    }

    private function ownProductOrFail(int $id): Product
    {
        $product = Auth::guard('member')->user()->products()->find($id);

        abort_if($product === null, 404);

        return $product;
    }

    public function render()
    {
        return view('livewire.portal.products', ['products' => $this->products]);
    }
}
