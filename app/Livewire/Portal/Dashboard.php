<?php

namespace App\Livewire\Portal;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.portal')]
class Dashboard extends Component
{
    public function getMemberProperty()
    {
        return Auth::guard('member')->user();
    }

    public function render()
    {
        return view('livewire.portal.dashboard', ['member' => $this->member]);
    }
}
