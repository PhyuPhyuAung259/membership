<?php

namespace App\Livewire;

use App\Models\BusinessType;
use App\Models\Member;
use App\Models\MemberType;
use App\Rules\WordCountBetween;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Renders at /members/create for two very different audiences: a signed-in
 * staff member adding a company, and a member of the public self-registering
 * from a link. The form is identical either way; only what happens on save
 * (and which layout wraps the page — see render()) differs.
 */
class MemberCreate extends Component
{
    use WithFileUploads;

    public string $company_name = '';
    public string $business_type_id = '';
    public string $email = '';
    public string $phone = '';
    public string $contact_person = '';
    public string $contact_person_phone = '';
    public string $contact_person_position = '';
    public string $member_type_id = '';
    public string $join_date = '';
    public string $address = '';
    public string $about = '';
    public string $notes = '';
    public bool $marketing_opt_in = true;

    public $logo = null;
    public $registration_document = null;

    public bool $submitted = false;

    public function mount(): void
    {
        $this->join_date = now()->toDateString();
    }

    public function getBusinessTypesProperty()
    {
        return BusinessType::alphabetical()->get();
    }

    public function getMemberTypesProperty()
    {
        return MemberType::ranked()->get();
    }

    /** The tier fee shown in the read-only fee field; blank until a tier is picked. */
    public function getSelectedFeeProperty(): ?string
    {
        if (! $this->member_type_id) {
            return null;
        }

        return $this->memberTypes->firstWhere('id', (int) $this->member_type_id)?->monthly_fee;
    }

    public function getAboutWordCountProperty(): int
    {
        return str_word_count(strip_tags($this->about));
    }

    public function save(): void
    {
        $data = $this->validate([
            'company_name' => 'required|string|max:200',
            'business_type_id' => 'nullable|exists:business_types,id',
            'email' => 'required|email|max:254|unique:members,email',
            'phone' => 'nullable|string|max:40',
            'contact_person' => 'nullable|string|max:200',
            'contact_person_phone' => 'nullable|string|max:40',
            'contact_person_position' => 'nullable|string|max:120',
            'member_type_id' => 'nullable|exists:member_types,id',
            'join_date' => 'required|date',
            'address' => 'nullable|string|max:500',
            'about' => ['nullable', 'string', new WordCountBetween(100, 200)],
            'notes' => 'nullable|string|max:4000',
            'logo' => 'nullable|image|max:2048',
            'registration_document' => 'nullable|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $data['email'] = mb_strtolower($data['email']);
        $data['business_type_id'] = $data['business_type_id'] ?: null;
        $data['member_type_id'] = $data['member_type_id'] ?: null;
        $data['address'] = $data['address'] ?: null;
        $data['about'] = $data['about'] ?: null;

        // No manual override here — the fee field is read-only, so the tier's
        // fee applies. monthly_fee stays null; effectiveMonthlyFee() reads it.
        unset($data['logo'], $data['registration_document']);

        // Staff add a member directly; anyone else filling the same public
        // form is unvetted, so their submission waits for review.
        $data['status'] = auth()->check() ? 'active' : 'pending';

        $member = new Member($data);
        $member->marketing_opt_in = $this->marketing_opt_in;
        $member->unsubscribed_at = $this->marketing_opt_in ? null : now();

        if ($this->logo) {
            $member->logo_path = $this->logo->store('logos', 'public');
        }

        if ($this->registration_document) {
            $member->registration_document_path = $this->registration_document->store('registrations', 'local');
        }

        $member->save();

        if (auth()->check()) {
            session()->flash('status', "{$member->company_name} added.");
            $this->redirectRoute('members', navigate: true);

            return;
        }

        // A public visitor isn't signed in, so there's nowhere staff-facing
        // to send them — show a thank-you message on this same page instead.
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.member-create', [
            'businessTypes' => $this->businessTypes,
            'memberTypes' => $this->memberTypes,
            'selectedFee' => $this->selectedFee,
            'aboutWordCount' => $this->aboutWordCount,
        ])->layout(auth()->check() ? 'layouts.app' : 'layouts.public');
    }
}
