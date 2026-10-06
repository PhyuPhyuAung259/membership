<?php

namespace App\Livewire\Portal;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.portal')]
class Documents extends Component
{
    use WithFileUploads;

    public $registrationDocument = null;

    public function upload(): void
    {
        $this->validate([
            'registrationDocument' => 'required|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $member = Auth::guard('member')->user();

        // Same disk as staff uploads (local, not public) — a business
        // registration document carries company numbers and addresses, and
        // the public disk is served with no auth by the web server.
        $path = $this->registrationDocument->store('registrations', 'local');
        $member->recordRegistrationDocument($path);

        $this->registrationDocument = null;
        session()->flash('status', 'Document uploaded.');
    }

    public function render()
    {
        return view('livewire.portal.documents', ['member' => Auth::guard('member')->user()]);
    }
}
