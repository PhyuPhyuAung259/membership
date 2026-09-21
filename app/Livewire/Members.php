<?php

namespace App\Livewire;

use App\Models\BusinessType;
use App\Models\Member;
use App\Models\MemberType;
use App\Models\Payment;
use App\Services\PaymentRecorder;
use Livewire\Attributes\Url;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Members extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filter = 'all';

    public ?int $detailId = null;
    public ?int $payingId = null;
    public ?int $editingId = null;

    // Payment form
    public int $months = 1;
    public string $amount = '';
    public string $paidOn = '';
    public string $method = 'bank_transfer';
    public string $reference = '';
    public string $note = '';

    // Member form
    public array $form = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function getMembersProperty()
    {
        $query = Member::query()->with('memberType')->search($this->search);

        $query = match ($this->filter) {
            'overdue' => $query->overdue(),
            'due_soon' => $query->dueWithin(7),
            'current' => $query->current(),
            'lapsed' => $query->lapsed(),
            'cancelled' => $query->where('status', 'cancelled'),
            default => $query,
        };

        return $query
            ->orderByRaw("CASE WHEN paid_through IS NULL THEN 0 WHEN paid_through < CURRENT_DATE THEN 1 ELSE 2 END")
            ->orderByRaw('paid_through ASC NULLS FIRST')
            ->orderBy('company_name')
            ->paginate(25);
    }

    /* ------------------------------------------------------------ detail */

    public function view(int $id): void
    {
        $this->detailId = $id;
    }

    public function getDetailProperty(): ?Member
    {
        return $this->detailId
            ? Member::with(['memberType', 'businessType', 'payments.recordedBy', 'emails' => fn ($q) => $q->latest()->limit(25)])
                ->find($this->detailId)
            : null;
    }

    public function closeAll(): void
    {
        $this->reset(['detailId', 'payingId', 'editingId']);
    }

    /* ----------------------------------------------------------- payment */

    public function startPayment(int $id, PaymentRecorder $recorder): void
    {
        $member = Member::findOrFail($id);
        $this->payingId = $id;
        $this->months = 1;
        $this->paidOn = now()->toDateString();
        $this->method = 'bank_transfer';
        $this->reference = '';
        $this->note = '';

        $preview = $recorder->preview($member, 1);
        $this->amount = (string) $preview['suggested_amount'];
    }

    public function updatedMonths(PaymentRecorder $recorder): void
    {
        if (! $this->payingId) {
            return;
        }

        $preview = $recorder->preview(Member::findOrFail($this->payingId), (int) $this->months);
        $this->amount = (string) $preview['suggested_amount'];
    }

    public function getPeriodPreviewProperty(): ?array
    {
        if (! $this->payingId) {
            return null;
        }

        return app(PaymentRecorder::class)
            ->preview(Member::findOrFail($this->payingId), (int) $this->months);
    }

    public function savePayment(PaymentRecorder $recorder): void
    {
        $data = $this->validate([
            'months' => 'required|integer|min:1|max:36',
            'amount' => 'required|numeric|min:0',
            'paidOn' => 'required|date',
            'method' => 'required|in:' . implode(',', Payment::METHODS),
            'reference' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:1000',
        ]);

        $member = Member::findOrFail($this->payingId);

        $recorder->record($member, [
            'months' => (int) $data['months'],
            'amount' => $data['amount'],
            'paid_on' => $data['paidOn'],
            'method' => $data['method'],
            'reference' => $data['reference'] ?: null,
            'note' => $data['note'] ?: null,
        ]);

        $this->payingId = null;
        session()->flash('status', "Payment recorded for {$member->company_name}.");
    }

    public function deletePayment(int $paymentId): void
    {
        // Deleting a payment is allowed, unlike deleting a member: a mistyped
        // payment is a data error, not history. The trigger pulls the
        // member's coverage back automatically.
        Payment::findOrFail($paymentId)->delete();
        session()->flash('status', 'Payment removed. Coverage recalculated.');
    }

    /* ------------------------------------------------------ member form */

    public function getBusinessTypesProperty()
    {
        return BusinessType::alphabetical()->get();
    }

    public function getMemberTypesProperty()
    {
        return MemberType::ranked()->get();
    }

    public function startEdit(?int $id = null): void
    {
        $this->editingId = $id;

        $member = $id ? Member::findOrFail($id) : null;

        $this->form = [
            'company_name' => $member->company_name ?? '',
            'business_type_id' => $member->business_type_id ?? '',
            'email' => $member->email ?? '',
            'phone' => $member->phone ?? '',
            'contact_person' => $member->contact_person ?? '',
            'contact_person_position' => $member->contact_person_position ?? '',
            'member_type_id' => $member->member_type_id ?? '',
            'monthly_fee' => $member && $member->monthly_fee !== null ? (string) $member->monthly_fee : '',
            'join_date' => $member?->join_date->format('Y-m-d') ?? now()->toDateString(),
            'notes' => $member->notes ?? '',
            'marketing_opt_in' => $member ? ($member->marketing_opt_in && ! $member->unsubscribed_at) : true,
        ];
    }

    public function saveMember(): void
    {
        $unique = 'unique:members,email' . ($this->editingId ? ",{$this->editingId}" : '');

        $data = $this->validate([
            'form.company_name' => 'required|string|max:200',
            'form.business_type_id' => 'nullable|exists:business_types,id',
            'form.email' => "required|email|max:254|{$unique}",
            'form.phone' => 'nullable|string|max:40',
            'form.contact_person' => 'nullable|string|max:200',
            'form.contact_person_position' => 'nullable|string|max:120',
            'form.member_type_id' => 'nullable|exists:member_types,id',
            'form.monthly_fee' => 'nullable|numeric|min:0',
            'form.join_date' => 'required|date',
            'form.notes' => 'nullable|string|max:4000',
            'form.marketing_opt_in' => 'boolean',
        ])['form'];

        $data['email'] = mb_strtolower($data['email']);
        $data['business_type_id'] = $data['business_type_id'] ?: null;
        $data['member_type_id'] = $data['member_type_id'] ?: null;

        // Blank means "use the tier's fee" — an override of 0 is a deliberate
        // free membership and must not be confused with "no override set".
        $data['monthly_fee'] = $data['monthly_fee'] !== '' && $data['monthly_fee'] !== null
            ? $data['monthly_fee']
            : null;

        // Re-subscribing must clear the timestamp, or the consent check keeps
        // blocking announcements even though the box is ticked.
        $optIn = (bool) ($data['marketing_opt_in'] ?? true);
        $member = $this->editingId ? Member::findOrFail($this->editingId) : new Member();

        $member->fill($data);
        $member->marketing_opt_in = $optIn;
        $member->unsubscribed_at = $optIn ? null : ($member->unsubscribed_at ?? now());
        $member->save();

        $this->editingId = null;
        $this->form = [];
        session()->flash('status', 'Member saved.');
    }

    public function cancelMembership(int $id): void
    {
        Member::findOrFail($id)->update(['status' => 'cancelled']);
        session()->flash('status', 'Membership cancelled. Payment history kept.');
    }

    public function reinstate(int $id): void
    {
        $member = Member::findOrFail($id);
        $member->update([
            'status' => ($member->paid_through && $member->dayOffset() <= 0) ? 'active' : 'lapsed',
        ]);
        session()->flash('status', 'Membership reinstated.');
    }

    public function render()
    {
        return view('livewire.members', [
            'members' => $this->members,
            'detail' => $this->detail,
            'periodPreview' => $this->periodPreview,
            'businessTypes' => $this->businessTypes,
            'memberTypes' => $this->memberTypes,
        ]);
    }
}
