<?php

namespace App\Livewire\Portal;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.portal-guest')]
class ForgotPassword extends Component
{
    public string $email = '';
    public ?string $status = null;

    /**
     * Always reports success, whether or not the email matched a member —
     * confirming "no account" here would let anyone probe which companies
     * are registered.
     */
    public function sendLink(): void
    {
        $this->validate(['email' => 'required|email']);

        Password::broker('members')->sendResetLink(['email' => $this->email]);

        $this->status = "If {$this->email} has a membership on file, a reset link is on its way.";
    }

    public function render()
    {
        return view('livewire.portal.forgot-password');
    }
}
