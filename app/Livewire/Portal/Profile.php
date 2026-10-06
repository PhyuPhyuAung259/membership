<?php

namespace App\Livewire\Portal;

use App\Models\BusinessType;
use App\Rules\WordCountBetween;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * What a member can change about their own listing. Deliberately narrower
 * than the staff edit form on the admin Members page: no email (that's the
 * login identity), no member type or fee override, no status, no notes —
 * those stay staff-controlled. Everything here is the member's own public
 * profile and contact details.
 */
#[Layout('layouts.portal')]
class Profile extends Component
{
    use WithFileUploads;

    public array $form = [];
    public $logo = null;

    public function mount(): void
    {
        $member = Auth::guard('member')->user();

        $this->form = [
            'company_name' => $member->company_name,
            'business_type_id' => $member->business_type_id ?? '',
            'phone' => $member->phone ?? '',
            'contact_person' => $member->contact_person ?? '',
            'contact_person_phone' => $member->contact_person_phone ?? '',
            'contact_person_position' => $member->contact_person_position ?? '',
            'address' => $member->address ?? '',
            'about' => $member->about ?? '',
            'marketing_opt_in' => $member->marketing_opt_in && ! $member->unsubscribed_at,
        ];
    }

    public function getBusinessTypesProperty()
    {
        return BusinessType::alphabetical()->get();
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.company_name' => 'required|string|max:200',
            'form.business_type_id' => 'nullable|exists:business_types,id',
            'form.phone' => 'nullable|string|max:40',
            'form.contact_person' => 'nullable|string|max:200',
            'form.contact_person_phone' => 'nullable|string|max:40',
            'form.contact_person_position' => 'nullable|string|max:120',
            'form.address' => 'nullable|string|max:500',
            'form.about' => ['nullable', 'string', new WordCountBetween(100, 200)],
            'form.marketing_opt_in' => 'boolean',
            'logo' => 'nullable|image|max:2048',
        ])['form'];

        $data['business_type_id'] = $data['business_type_id'] ?: null;
        $data['address'] = $data['address'] ?: null;
        $data['about'] = $data['about'] ?: null;

        $member = Auth::guard('member')->user();

        if ($this->logo) {
            $data['logo_path'] = $this->logo->store('logos', 'public');
        }

        $optIn = (bool) ($data['marketing_opt_in'] ?? true);
        unset($data['marketing_opt_in']);

        $member->fill($data);
        $member->marketing_opt_in = $optIn;
        $member->unsubscribed_at = $optIn ? null : ($member->unsubscribed_at ?? now());
        $member->save();

        $this->logo = null;
        session()->flash('status', 'Profile saved.');
    }

    public function render()
    {
        return view('livewire.portal.profile', [
            'member' => Auth::guard('member')->user(),
            'businessTypes' => $this->businessTypes,
        ]);
    }
}
